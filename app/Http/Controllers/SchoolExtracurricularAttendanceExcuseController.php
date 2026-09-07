<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SchoolExtracurricular;
use App\Models\SchoolExtracurricularAttendance;
use App\Models\SchoolExtracurricularAttendanceExcuse;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\StudentWhatsappNotifier;

class SchoolExtracurricularAttendanceExcuseController extends Controller
{
    public function index(Request $request, Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $academicYear = $this->currentAcademicYear();
        $dateInput = $request->query('date');
        try {
            $dateObject = is_string($dateInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)
                ? Carbon::createFromFormat('Y-m-d', $dateInput)->startOfDay()
                : today();
            if (is_string($dateInput) && $dateObject->toDateString() !== $dateInput) {
                $dateObject = today();
            }
        } catch (\Throwable) {
            $dateObject = today();
        }
        $date = $dateObject->toDateString();
        $status = in_array($request->query('status'), ['izin', 'sakit', 'lainnya'], true)
            ? $request->query('status')
            : null;
        $className = filled($request->query('class_name'))
            ? trim((string) $request->query('class_name'))
            : null;
        $q = filled($request->query('q'))
            ? trim((string) $request->query('q'))
            : null;

        $extracurriculars = SchoolExtracurricular::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->orderBy('name')
            ->get();

        $requestedId = $request->integer('extracurricular_id');
        $selectedDay = $dateObject->dayOfWeek;
        $selectedExtracurricular = $extracurriculars->firstWhere('id', $requestedId)
            ?? $extracurriculars->firstWhere('schedule_day', $selectedDay)
            ?? $extracurriculars->first();
        $scheduledDate = $selectedExtracurricular
            && $selectedExtracurricular->schedule_day === $selectedDay;

        $enrollments = collect();
        if ($selectedExtracurricular) {
            $enrollmentQuery = StudentEnrollment::query()
                ->with('student')
                ->where('institution_id', $institution->id)
                ->where('academic_year', $academicYear)
                ->where('is_active', true)
                ->whereHas('student', fn ($query) => $query->where('is_active', true));

            $this->applyLevelScope($enrollmentQuery, $selectedExtracurricular->level);
            $enrollments = $enrollmentQuery->get();
        }

        $studentOptions = $enrollments
            ->sortBy(fn ($enrollment) => strtolower($enrollment->student?->name ?? ''))
            ->values();
        $classOptions = $enrollments
            ->pluck('class_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $baseQuery = SchoolExtracurricularAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->where('school_extracurricular_id', $selectedExtracurricular?->id ?? 0)
            ->whereDate('attendance_date', $date);

        $totals = [
            'izin' => (clone $baseQuery)->where('status', 'izin')->count(),
            'sakit' => (clone $baseQuery)->where('status', 'sakit')->count(),
            'lainnya' => (clone $baseQuery)->where('status', 'lainnya')->count(),
        ];

        $excuses = (clone $baseQuery)
            ->with(['extracurricular', 'student', 'recorder'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($className, fn ($query) => $query->where('class_name_snapshot', $className))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($search) use ($q) {
                    $search->where('nis_snapshot', 'like', "%{$q}%")
                        ->orWhereHas('student', function ($student) use ($q) {
                            $student->where('name', 'like', "%{$q}%")
                                ->orWhere('nis', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('school-extracurricular-attendance.excuses', compact(
            'institution',
            'academicYear',
            'date',
            'status',
            'className',
            'q',
            'extracurriculars',
            'selectedExtracurricular',
            'scheduledDate',
            'studentOptions',
            'classOptions',
            'totals',
            'excuses'
        ));
    }

    public function store(
        Request $request,
        Institution $institution,
        StudentWhatsappNotifier $whatsapp
    ): RedirectResponse
    {
        $this->authorizeInstitution($institution);

        $data = $request->validate([
            'school_extracurricular_id' => ['required', 'integer', 'exists:school_extracurriculars,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:izin,sakit,lainnya'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => [
                'nullable',
                'required_if:status,izin,sakit',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ], [
            'attachment.required_if' => 'Surat wajib dilampirkan untuk status izin atau sakit.',
            'attachment.mimes' => 'Lampiran harus berupa PDF, JPG, JPEG, atau PNG.',
            'attachment.max' => 'Ukuran lampiran maksimal 5 MB.',
        ]);

        $extracurricular = SchoolExtracurricular::query()
            ->where('id', $data['school_extracurricular_id'])
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->first();

        if (!$extracurricular) {
            return back()
                ->withInput()
                ->withErrors(['school_extracurricular_id' => 'Ekstrakurikuler tidak ditemukan atau sedang nonaktif.']);
        }

        $attendanceDate = Carbon::parse($data['attendance_date']);
        if ($attendanceDate->dayOfWeek !== $extracurricular->schedule_day) {
            return back()
                ->withInput()
                ->withErrors([
                    'attendance_date' => $extracurricular->name.' hanya dijadwalkan hari '.$extracurricular->dayLabel().'.',
                ]);
        }

        $academicYear = $this->currentAcademicYear();
        $enrollment = StudentEnrollment::query()
            ->with('student')
            ->where('institution_id', $institution->id)
            ->where('student_id', $data['student_id'])
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->first();

        if (!$enrollment) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'Siswa tidak terdaftar aktif di '.$institution->short_name.'.']);
        }

        $studentLevel = $this->normalizeLevel($enrollment->level);
        if (!$studentLevel) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'Jenjang siswa belum diisi MTs/MA pada data lembaga.']);
        }

        if (!$this->levelMatches($extracurricular->level, $studentLevel)) {
            return back()
                ->withInput()
                ->withErrors([
                    'student_id' => $enrollment->student->name.' tidak termasuk jenjang peserta '.$extracurricular->name.'.',
                ]);
        }

        $hasAttendance = SchoolExtracurricularAttendance::query()
            ->where('institution_id', $institution->id)
            ->where('school_extracurricular_id', $extracurricular->id)
            ->where('student_id', $data['student_id'])
            ->whereDate('attendance_date', $data['attendance_date'])
            ->exists();

        if ($hasAttendance) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'Siswa sudah melakukan scan pada kegiatan dan tanggal tersebut.']);
        }

        $alreadyExists = SchoolExtracurricularAttendanceExcuse::query()
            ->where('school_extracurricular_id', $extracurricular->id)
            ->where('student_id', $data['student_id'])
            ->whereDate('attendance_date', $data['attendance_date'])
            ->exists();

        if ($alreadyExists) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'Status izin/sakit siswa untuk kegiatan dan tanggal tersebut sudah tercatat.']);
        }

        $attachment = $request->file('attachment');
        $storedAttachment = $attachment ? $this->storeAttachment($attachment) : null;

        try {
            DB::transaction(function () use (
                $institution,
                $extracurricular,
                $enrollment,
                $studentLevel,
                $data,
                $academicYear,
                $storedAttachment
            ) {
                SchoolExtracurricularAttendanceExcuse::create([
                    'institution_id' => $institution->id,
                    'school_extracurricular_id' => $extracurricular->id,
                    'student_id' => $enrollment->student_id,
                    'attendance_date' => $data['attendance_date'],
                    'academic_year' => $academicYear,
                    'nis_snapshot' => $enrollment->student->nis,
                    'level_snapshot' => $studentLevel,
                    'class_name_snapshot' => $enrollment->class_name,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'attachment_path' => $storedAttachment['path'] ?? null,
                    'attachment_original_name' => $storedAttachment['original_name'] ?? null,
                    'attachment_mime' => $storedAttachment['mime'] ?? null,
                    'attachment_size' => $storedAttachment['size'] ?? null,
                    'recorded_by' => auth()->id(),
                ]);
            });
        } catch (\Throwable $exception) {
            if ($storedAttachment) {
                File::delete(storage_path('app/private/'.$storedAttachment['path']));
            }

            throw $exception;
        }

        $whatsapp->excuse(
            $institution,
            $enrollment->student,
            'Ekstrakurikuler '.$extracurricular->name,
            $data['status'],
            $data['attendance_date'],
            $data['notes'] ?? null
        );

        return redirect()
            ->route('school-extracurricular-attendance.excuses.index', [
                'institution' => $institution,
                'extracurricular_id' => $extracurricular->id,
                'date' => $data['attendance_date'],
            ])
            ->with(
                'success',
                'Status '.strtoupper($data['status']).' berhasil dicatat untuk '.$enrollment->student->name.' pada '.$extracurricular->name.'.'
            );
    }

    public function download(
        Institution $institution,
        SchoolExtracurricularAttendanceExcuse $excuse
    ): BinaryFileResponse {
        $this->authorizeInstitution($institution);
        $this->authorizeExcuse($institution, $excuse);

        abort_unless($excuse->attachment_path, 404, 'Lampiran tidak tersedia.');

        $path = storage_path('app/private/'.$excuse->attachment_path);
        abort_unless(File::exists($path), 404, 'File lampiran tidak ditemukan.');

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $studentName = $excuse->student?->name ?? 'siswa';
        $activityName = $excuse->extracurricular?->name ?? 'ekstrakurikuler';
        $filename = Str::slug(
            'surat-'.$excuse->status.'-'.$activityName.'-'.$studentName.'-'.$excuse->attendance_date->format('Y-m-d')
        ).'.'.$extension;

        return response()->download($path, $filename);
    }

    public function destroy(
        Institution $institution,
        SchoolExtracurricularAttendanceExcuse $excuse
    ): RedirectResponse {
        $this->authorizeInstitution($institution);
        $this->authorizeExcuse($institution, $excuse);

        $date = $excuse->attendance_date->toDateString();
        $extracurricularId = $excuse->school_extracurricular_id;
        $attachmentPath = $excuse->attachment_path;

        $excuse->delete();

        if ($attachmentPath) {
            File::delete(storage_path('app/private/'.$attachmentPath));
        }

        return redirect()
            ->route('school-extracurricular-attendance.excuses.index', [
                'institution' => $institution,
                'extracurricular_id' => $extracurricularId,
                'date' => $date,
            ])
            ->with('success', 'Status izin/sakit ekstrakurikuler berhasil dihapus.');
    }

    private function storeAttachment(UploadedFile $file): array
    {
        $directory = storage_path('app/private/school-extracurricular-attendance/letters');
        File::ensureDirectoryExists($directory, 0755, true);

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid().'.'.$extension;
        $relativePath = 'school-extracurricular-attendance/letters/'.$filename;
        $metadata = [
            'path' => $relativePath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];

        $file->move($directory, $filename);

        return $metadata;
    }

    private function applyLevelScope(Builder $query, string $activityLevel): void
    {
        if ($activityLevel === 'mts_ma') {
            $query->whereIn(DB::raw('LOWER(TRIM(level))'), [
                'mts',
                'madrasah tsanawiyah',
                'ma',
                'aliyah',
                'madrasah aliyah',
                'mts_ma',
                'mts & ma',
                'mts dan ma',
                'mts/ma',
                'mts-ma',
            ]);

            return;
        }

        $levels = $activityLevel === 'mts'
            ? ['mts', 'madrasah tsanawiyah']
            : ['ma', 'aliyah', 'madrasah aliyah'];

        $query->whereIn(DB::raw('LOWER(TRIM(level))'), $levels);
    }

    private function normalizeLevel(?string $level): ?string
    {
        return match (mb_strtolower(trim((string) $level))) {
            'mts', 'madrasah tsanawiyah' => 'mts',
            'ma', 'aliyah', 'madrasah aliyah' => 'ma',
            'mts_ma', 'mts & ma', 'mts dan ma', 'mts/ma', 'mts-ma' => 'mts_ma',
            default => null,
        };
    }

    private function levelMatches(string $activityLevel, string $studentLevel): bool
    {
        return $activityLevel === 'mts_ma'
            ? in_array($studentLevel, ['mts', 'ma', 'mts_ma'], true)
            : $activityLevel === $studentLevel;
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless($institution->is_active && $institution->code === 'sekolah-pagi', 404);

        $user = auth()->user();
        $hasAccess = $user->hasRole('admin') || $user->institutions()
            ->where('institutions.id', $institution->id)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke lembaga ini.');
    }

    private function authorizeExcuse(
        Institution $institution,
        SchoolExtracurricularAttendanceExcuse $excuse
    ): void {
        abort_unless($excuse->institution_id === $institution->id, 404);
    }

    private function currentAcademicYear(): string
    {
        return now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
    }
}
