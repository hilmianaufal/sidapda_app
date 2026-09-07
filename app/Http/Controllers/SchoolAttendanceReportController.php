<?php

namespace App\Http\Controllers;

use App\Exports\SchoolAttendanceDailyExport;
use App\Models\Institution;
use App\Models\StudentEnrollment;
use App\Services\SchoolAttendanceReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SchoolAttendanceReportController extends Controller
{
    public function __construct(
        private readonly SchoolAttendanceReportService $reportService
    ) {}

    public function index(Request $request, Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $date = $this->normalizeDate($request->query('date'));
        $level = $this->normalizeFilter($request->query('level'), 20);
        $className = $this->normalizeFilter($request->query('class_name'), 50);
        $academicYear = $this->currentAcademicYear();

        $report = $this->reportService->build(
            $institution,
            $date,
            $academicYear,
            $level,
            $className
        );

        $page = max(1, $request->integer('page', 1));
        $perPage = 25;
        $rows = new LengthAwarePaginator(
            $report['rows']->forPage($page, $perPage)->values(),
            $report['rows']->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ]
        );

        $optionQuery = StudentEnrollment::query()
            ->where('institution_id', $institution->id)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->whereHas('student', fn ($query) => $query->where('is_active', true));

        $levelOptions = (clone $optionQuery)
            ->pluck('level')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $classOptions = (clone $optionQuery)
            ->when($level, fn ($query) => $query->where('level', $level))
            ->pluck('class_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('school-attendance.report', [
            'institution' => $institution,
            'academicYear' => $academicYear,
            'date' => $date,
            'level' => $level,
            'className' => $className,
            'levelOptions' => $levelOptions,
            'classOptions' => $classOptions,
            'summary' => $report['summary'],
            'rows' => $rows,
        ]);
    }

    public function export(Request $request, Institution $institution): BinaryFileResponse
    {
        $this->authorizeInstitution($institution);

        $date = $this->normalizeDate($request->query('date'));
        $level = $this->normalizeFilter($request->query('level'), 20);
        $className = $this->normalizeFilter($request->query('class_name'), 50);
        $academicYear = $this->currentAcademicYear();

        $filenameParts = array_filter([
            'rekap-absensi-'.$institution->code,
            $date,
            $level,
            $className,
        ]);
        $filename = Str::slug(implode('-', $filenameParts)).'.xlsx';

        return Excel::download(
            new SchoolAttendanceDailyExport(
                $institution->id,
                $date,
                $academicYear,
                $level,
                $className
            ),
            $filename
        );
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless(
            $institution->is_active
                && in_array($institution->code, ['mi', 'sekolah-pagi'], true),
            404
        );

        $user = auth()->user();
        $hasAccess = $user->hasRole('admin') || $user->institutions()
            ->where('institutions.id', $institution->id)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke lembaga ini.');
    }

    private function currentAcademicYear(): string
    {
        return now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
    }

    private function normalizeDate(mixed $value): string
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return today()->toDateString();
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year)
            ? $value
            : today()->toDateString();
    }

    private function normalizeFilter(mixed $value, int $maxLength): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' && mb_strlen($value) <= $maxLength
            ? $value
            : null;
    }
}
