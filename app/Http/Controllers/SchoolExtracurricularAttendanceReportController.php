<?php

namespace App\Http\Controllers;

use App\Exports\SchoolExtracurricularAttendanceDailyExport;
use App\Models\Institution;
use App\Models\SchoolExtracurricular;
use App\Services\SchoolExtracurricularAttendanceReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SchoolExtracurricularAttendanceReportController extends Controller
{
    public function __construct(
        private readonly SchoolExtracurricularAttendanceReportService $reportService
    ) {}

    public function index(Request $request, Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $date = $this->normalizeDate($request->query('date'));
        $academicYear = $this->academicYearForDate($date);
        $className = $this->normalizeFilter($request->query('class_name'), 50);

        $extracurriculars = SchoolExtracurricular::query()
            ->where('institution_id', $institution->id)
            ->orderByDesc('is_active')
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->orderBy('name')
            ->get();

        $requestedId = $request->integer('extracurricular_id');
        $selectedDay = Carbon::parse($date)->dayOfWeek;
        $selectedExtracurricular = $extracurriculars->firstWhere('id', $requestedId)
            ?? $extracurriculars->first(
                fn ($item) => $item->is_active && $item->schedule_day === $selectedDay
            )
            ?? $extracurriculars->firstWhere('is_active', true)
            ?? $extracurriculars->first();

        if ($selectedExtracurricular) {
            $report = $this->reportService->build(
                $institution,
                $selectedExtracurricular,
                $date,
                $academicYear,
                $className
            );
            $classOptions = $this->reportService->classOptions(
                $institution,
                $selectedExtracurricular,
                $academicYear
            );
        } else {
            $report = [
                'rows' => collect(),
                'summary' => $this->emptySummary(),
            ];
            $classOptions = collect();
        }

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

        return view('school-extracurricular-attendance.report', [
            'institution' => $institution,
            'academicYear' => $academicYear,
            'date' => $date,
            'className' => $className,
            'extracurriculars' => $extracurriculars,
            'selectedExtracurricular' => $selectedExtracurricular,
            'scheduledDate' => $selectedExtracurricular
                && $selectedExtracurricular->schedule_day === $selectedDay,
            'classOptions' => $classOptions,
            'summary' => $report['summary'],
            'rows' => $rows,
        ]);
    }

    public function export(Request $request, Institution $institution): BinaryFileResponse
    {
        $this->authorizeInstitution($institution);

        $extracurricular = SchoolExtracurricular::query()
            ->where('institution_id', $institution->id)
            ->where('id', $request->integer('extracurricular_id'))
            ->firstOrFail();
        $date = $this->normalizeDate($request->query('date'));
        $academicYear = $this->academicYearForDate($date);
        $className = $this->normalizeFilter($request->query('class_name'), 50);

        $filenameParts = array_filter([
            'rekap-absensi-ekstrakurikuler',
            $extracurricular->code,
            $date,
            $className,
        ]);
        $filename = Str::slug(implode('-', $filenameParts)).'.xlsx';

        return Excel::download(
            new SchoolExtracurricularAttendanceDailyExport(
                $institution->id,
                $extracurricular->id,
                $date,
                $academicYear,
                $className
            ),
            $filename
        );
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless($institution->is_active && in_array($institution->code, ['mts', 'ma'], true), 404);

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

    private function academicYearForDate(string $date): string
    {
        $value = Carbon::parse($date);

        return $value->month >= 7
            ? $value->year.'/'.($value->year + 1)
            : ($value->year - 1).'/'.$value->year;
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

    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'hadir' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'lainnya' => 0,
            'alpa' => 0,
        ];
    }
}
