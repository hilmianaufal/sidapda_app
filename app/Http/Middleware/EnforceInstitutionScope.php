<?php

namespace App\Http\Middleware;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\Institution;
use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceInstitutionScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('admin')) {
            return $next($request);
        }

        $route = $request->route();
        $routeName = $route?->getName() ?? '';
        $institution = $route?->parameter('institution');

        if ($institution !== null) {
            $institution = $institution instanceof Institution
                ? $institution
                : Institution::where('code', $institution)->firstOrFail();

            abort_unless($institution->is_active, 404);
            $this->authorizeCode($request, $institution->code);
        }

        if (str_starts_with($routeName, 'students.')) {
            $this->authorizeStudentModule($request, $routeName);
        }

        $requiredCode = match (true) {
            $routeName === 'activities.excuses.pondok' => 'ponpes',
            $routeName === 'activities.excuses.madad' => 'madad',
            str_starts_with($routeName, 'rekap-diniyah.') => 'madad',
            str_starts_with($routeName, 'rekap-kegiatan.') => 'ponpes',
            str_starts_with($routeName, 'rekap.prayer-summary.') => 'ponpes',
            str_starts_with($routeName, 'rekap.') => 'ponpes',
            str_starts_with($routeName, 'scan.') => 'ponpes',
            str_starts_with($routeName, 'boarding-movements.') => 'ponpes',
            str_starts_with($routeName, 'prayers.') => 'ponpes',
            default => null,
        };

        if ($requiredCode !== null) {
            $this->authorizeCode($request, $requiredCode);
        }

        if (str_starts_with($routeName, 'activities.')
            && ! in_array($routeName, ['activities.excuses.pondok', 'activities.excuses.madad'], true)) {
            $this->authorizeActivityModule($request);
        }

        return $next($request);
    }

    private function authorizeStudentModule(Request $request, string $routeName): void
    {
        $user = $request->user();

        abort_unless(
            $user->accessibleInstitutions()->exists(),
            403,
            'Akun ini belum memiliki akses lembaga.'
        );

        if ($routeName === 'students.attendance.show' || str_starts_with($routeName, 'students.id-card')) {
            $this->authorizeCode($request, 'ponpes');
        }

        if ($request->filled('institution_id')) {
            $institution = Institution::findOrFail($request->integer('institution_id'));
            abort_unless($user->canAccessInstitution($institution), 403, 'Anda tidak memiliki akses ke lembaga ini.');
        }

        $student = $request->route('student');

        if (! $student instanceof Student && is_scalar($student)) {
            $student = Student::findOrFail((int) $student);
        }

        if ($student instanceof Student) {
            abort_unless(
                $user->canAccessStudent($student),
                403,
                'Anda tidak memiliki akses ke data siswa ini.'
            );
        }
    }

    private function authorizeActivityModule(Request $request): void
    {
        $user = $request->user();
        $activity = $request->route('activity');

        if (! $activity instanceof Activity && is_scalar($activity)) {
            $activity = Activity::findOrFail((int) $activity);
        }

        if (! $activity instanceof Activity && $request->filled('activity_id')) {
            $activity = Activity::findOrFail($request->integer('activity_id'));
        }

        if (! $activity instanceof Activity && $request->filled('attendance_id')) {
            $activity = ActivityAttendance::query()
                ->with('session.activity')
                ->findOrFail($request->integer('attendance_id'))
                ->session?->activity;
        }

        if ($activity instanceof Activity) {
            $this->authorizeCode($request, $activity->category === 'diniyah' ? 'madad' : 'ponpes');

            $requestedCategory = $request->input('category');
            if (in_array($requestedCategory, ['umum', 'diniyah'], true)
                && $requestedCategory !== $activity->category) {
                $this->authorizeCode($request, $requestedCategory === 'diniyah' ? 'madad' : 'ponpes');
            }

            return;
        }

        $category = $request->input('category')
            ?? $request->route('category');

        if (in_array($category, ['umum', 'diniyah'], true)) {
            $this->authorizeCode($request, $category === 'diniyah' ? 'madad' : 'ponpes');
            return;
        }

        $canPondok = $user->canAccessInstitution('ponpes');
        $canMadad = $user->canAccessInstitution('madad');

        abort_unless($canPondok || $canMadad, 403, 'Anda tidak memiliki akses ke modul kegiatan.');

        // Akun dengan satu lembaga langsung dikunci ke kategori lembaganya.
        if ($canPondok xor $canMadad) {
            $category = $canMadad ? 'diniyah' : 'umum';
            $request->merge(['category' => $category]);
            $request->query->set('category', $category);
        }
    }

    private function authorizeCode(Request $request, string $code): void
    {
        abort_unless(
            $request->user()->canAccessInstitution($code),
            403,
            'Anda tidak memiliki akses ke lembaga ini.'
        );
    }
}
