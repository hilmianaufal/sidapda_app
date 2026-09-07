<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use App\Services\StudentPromotionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class StudentPromotionController extends Controller
{
    public function __construct(private readonly StudentPromotionService $promotionService)
    {
    }

    public function index(Request $request): View
    {
        $institutions = $this->accessibleInstitutions()->get();
        $institution = $this->selectedInstitution($request, $institutions->first());
        $currentAcademicYear = Student::academicYearForDate();
        $previousAcademicYear = $this->promotionService->previousAcademicYear($currentAcademicYear);
        $academicYears = collect([$currentAcademicYear]);
        $fromAcademicYear = $currentAcademicYear;
        $groups = collect();
        $recentPromotions = collect();

        if ($institution !== null) {
            $availableYears = StudentEnrollment::query()
                ->where('institution_id', $institution->id)
                ->distinct()
                ->orderByDesc('academic_year')
                ->pluck('academic_year');

            $academicYears = $availableYears
                ->push($currentAcademicYear)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values();

            $requestedYear = $request->query('from_academic_year');
            $fromAcademicYear = is_string($requestedYear) && $academicYears->contains($requestedYear)
                ? $requestedYear
                : ($academicYears->contains($previousAcademicYear)
                    ? $previousAcademicYear
                    : ($academicYears->first() ?? $currentAcademicYear));

            $groups = $this->promotionService->previewGroups($institution, $fromAcademicYear);
            $recentPromotions = StudentPromotion::query()
                ->with(['student:id,name,nis', 'processor:id,name'])
                ->where('institution_id', $institution->id)
                ->latest('processed_at')
                ->limit(12)
                ->get();
        }

        $toAcademicYear = $this->promotionService->nextAcademicYear($fromAcademicYear);
        $canProcess = strcmp($toAcademicYear, $currentAcademicYear) <= 0;
        $summary = [
            'students' => $groups->sum('total'),
            'remaining' => $groups->sum('remaining'),
            'groups' => $groups->count(),
            'manual' => $groups->where('action', 'skip')->sum('remaining'),
        ];

        return view('students.promotions.index', compact(
            'institutions',
            'institution',
            'academicYears',
            'fromAcademicYear',
            'toAcademicYear',
            'currentAcademicYear',
            'canProcess',
            'groups',
            'summary',
            'recentPromotions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'institution_id' => ['required', 'integer'],
            'from_academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'groups' => ['required', 'array', 'min:1', 'max:100'],
            'groups.*.selected' => ['nullable', 'boolean'],
            'groups.*.source_class' => ['nullable', 'string', 'max:50'],
            'groups.*.source_level' => ['nullable', 'string', 'max:20'],
            'groups.*.action' => ['required', 'in:promote,graduate,skip'],
            'groups.*.target_class' => ['nullable', 'string', 'max:50'],
            'groups.*.target_level' => ['nullable', 'string', 'max:20'],
        ]);

        $institution = $this->accessibleInstitutions()->findOrFail($data['institution_id']);

        try {
            $toAcademicYear = $this->promotionService->nextAcademicYear($data['from_academic_year']);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['from_academic_year' => $exception->getMessage()]);
        }

        if (strcmp($toAcademicYear, Student::academicYearForDate()) > 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'from_academic_year' => "Tahun tujuan {$toAcademicYear} belum dimulai. Kenaikan dapat diproses mulai 1 Juli ".substr($toAcademicYear, 0, 4).'.',
                ]);
        }

        $selectedGroups = collect($data['groups'])
            ->filter(fn (array $group) => (bool) ($group['selected'] ?? false))
            ->values()
            ->all();

        if ($selectedGroups === []) {
            return back()
                ->withInput()
                ->withErrors(['groups' => 'Pilih minimal satu kelompok kelas yang akan diproses.']);
        }

        try {
            $result = $this->promotionService->processGroups(
                $institution,
                $data['from_academic_year'],
                $selectedGroups,
                auth()->id()
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['groups' => $exception->getMessage()]);
        }

        $message = "Kenaikan kelas {$institution->short_name} selesai. "
            ."Naik kelas: {$result['promoted']}, "
            ."lulus: {$result['graduated']}, "
            ."sudah terdaftar: {$result['already_enrolled']}, "
            ."sudah pernah diproses: {$result['already_processed']}.";

        return redirect()
            ->route('students.promotions.index', [
                'institution_id' => $institution->id,
                'from_academic_year' => $data['from_academic_year'],
            ])
            ->with('success', $message);
    }

    private function accessibleInstitutions(): Builder
    {
        $query = Institution::query()
            ->where('is_active', true)
            ->orderBy('sort_order');

        $user = auth()->user();

        if (! $user->hasRole('admin')) {
            $query->whereHas('users', fn (Builder $users) => $users
                ->where('users.id', $user->id)
                ->where('institution_user.is_active', true));
        }

        return $query;
    }

    private function selectedInstitution(Request $request, ?Institution $fallback): ?Institution
    {
        if (! $request->filled('institution_id')) {
            return $fallback;
        }

        return $this->accessibleInstitutions()->findOrFail($request->integer('institution_id'));
    }
}
