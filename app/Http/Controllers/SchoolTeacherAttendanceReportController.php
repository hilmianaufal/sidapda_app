<?php

namespace App\Http\Controllers;

use App\Exports\SchoolTeacherAttendanceDailyExport;
use App\Models\Institution;
use App\Services\SchoolTeacherAttendanceReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SchoolTeacherAttendanceReportController extends Controller
{
    public function __construct(
        private readonly SchoolTeacherAttendanceReportService $reportService
    ) {}

    public function index(Request $request, Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $date = $this->normalizeDate($request->query('date'));
        $levelOptions = $this->levelOptions($institution);
        $level = $this->normalizeLevel($request->query('level'), $levelOptions);
        $academicYear = $this->academicYearForDate($date);

        $report = $this->reportService->build($institution, $date, $level);

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

        return view('school-teacher-attendance.report', [
            'institution' => $institution,
            'academicYear' => $academicYear,
            'date' => $date,
            'level' => $level,
            'levelOptions' => $levelOptions,
            'summary' => $report['summary'],
            'rows' => $rows,
        ]);
    }

    public function export(Request $request, Institution $institution): BinaryFileResponse
    {
        $this->authorizeInstitution($institution);

        $date = $this->normalizeDate($request->query('date'));
        $level = $this->normalizeLevel(
            $request->query('level'),
            $this->levelOptions($institution)
        );
        $filename = Str::slug(implode('-', array_filter([
            'rekap-absensi-guru-'.$institution->code,
            $date,
            $level,
        ]))).'.xlsx';

        return Excel::download(
            new SchoolTeacherAttendanceDailyExport($institution->id, $date, $level),
            $filename
        );
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless(
            $institution->is_active
                && in_array($institution->code, ['mi', 'mts', 'ma'], true),
            404
        );

        $user = auth()->user();
        $hasAccess = $user->hasRole('admin') || $user->institutions()
            ->where('institutions.id', $institution->id)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke lembaga ini.');
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

    private function normalizeLevel(mixed $value, array $levelOptions): ?string
    {
        return is_string($value) && array_key_exists($value, $levelOptions)
            ? $value
            : null;
    }

    private function levelOptions(Institution $institution): array
    {
        return match ($institution->code) {
            'mi' => ['mi' => 'MI'],
            'mts' => ['mts' => 'MTs'],
            'ma' => ['ma' => 'MA'],
            default => [],
        };
    }

    private function academicYearForDate(string $date): string
    {
        [$year, $month] = array_map('intval', explode('-', $date));

        return $month >= 7
            ? $year.'/'.($year + 1)
            : ($year - 1).'/'.$year;
    }
}
