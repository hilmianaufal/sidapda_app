<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SchoolTeacher;
use App\Models\SchoolTeacherAttendance;
use App\Models\SchoolTeacherAttendanceExcuse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SchoolTeacherAttendanceExcuseController extends Controller
{
    public function index(Request $request, Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $academicYear = $this->currentAcademicYear();
        $date = $request->query('date');
        $date = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            ? $date
            : today()->toDateString();
        $status = in_array($request->query('status'), ['izin', 'sakit', 'lainnya'], true)
            ? $request->query('status')
            : null;
        $levelOptions = $this->levelOptions($institution);
        $requestedLevel = $request->query('level');
        $level = is_string($requestedLevel) && array_key_exists($requestedLevel, $levelOptions)
            ? $requestedLevel
            : null;
        $q = filled($request->query('q'))
            ? trim((string) $request->query('q'))
            : null;

        $teacherOptions = SchoolTeacher::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $baseQuery = SchoolTeacherAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->whereDate('attendance_date', $date);

        $totals = [
            'izin' => (clone $baseQuery)->where('status', 'izin')->count(),
            'sakit' => (clone $baseQuery)->where('status', 'sakit')->count(),
            'lainnya' => (clone $baseQuery)->where('status', 'lainnya')->count(),
        ];

        $excuses = (clone $baseQuery)
            ->with(['teacher', 'recorder'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($level, fn ($query) => $query->where('level_snapshot', $level))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($search) use ($q) {
                    $search->where('teacher_code_snapshot', 'like', "%{$q}%")
                        ->orWhereHas('teacher', function ($teacher) use ($q) {
                            $teacher->where('name', 'like', "%{$q}%")
                                ->orWhere('teacher_code', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('school-teacher-attendance.excuses', compact(
            'institution',
            'academicYear',
            'date',
            'status',
            'level',
            'levelOptions',
            'q',
            'teacherOptions',
            'totals',
            'excuses'
        ));
    }

    public function store(Request $request, Institution $institution): RedirectResponse
    {
        $this->authorizeInstitution($institution);

        $data = $request->validate([
            'school_teacher_id' => ['required', 'integer', 'exists:school_teachers,id'],
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

        $teacher = SchoolTeacher::query()
            ->where('id', $data['school_teacher_id'])
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->first();

        if (!$teacher) {
            return back()
                ->withInput()
                ->withErrors(['school_teacher_id' => 'Guru tidak terdaftar aktif di '.$institution->short_name.'.']);
        }

        $hasAttendance = SchoolTeacherAttendance::query()
            ->where('institution_id', $institution->id)
            ->where('school_teacher_id', $teacher->id)
            ->whereDate('attendance_date', $data['attendance_date'])
            ->where(function ($query) {
                $query->whereNotNull('check_in_at')
                    ->orWhereNotNull('check_out_at');
            })
            ->exists();

        if ($hasAttendance) {
            return back()
                ->withInput()
                ->withErrors(['school_teacher_id' => 'Guru sudah memiliki scan masuk/pulang pada tanggal tersebut.']);
        }

        $alreadyExists = SchoolTeacherAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->where('school_teacher_id', $teacher->id)
            ->whereDate('attendance_date', $data['attendance_date'])
            ->exists();

        if ($alreadyExists) {
            return back()
                ->withInput()
                ->withErrors(['school_teacher_id' => 'Status izin/sakit guru pada tanggal tersebut sudah tercatat.']);
        }

        $attachment = $request->file('attachment');
        $storedAttachment = $attachment ? $this->storeAttachment($attachment) : null;

        try {
            SchoolTeacherAttendanceExcuse::create([
                'institution_id' => $institution->id,
                'school_teacher_id' => $teacher->id,
                'attendance_date' => $data['attendance_date'],
                'academic_year' => $this->currentAcademicYear(),
                'teacher_code_snapshot' => $teacher->teacher_code,
                'level_snapshot' => $teacher->level,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'attachment_path' => $storedAttachment['path'] ?? null,
                'attachment_original_name' => $storedAttachment['original_name'] ?? null,
                'attachment_mime' => $storedAttachment['mime'] ?? null,
                'attachment_size' => $storedAttachment['size'] ?? null,
                'recorded_by' => auth()->id(),
            ]);
        } catch (\Throwable $exception) {
            if ($storedAttachment) {
                File::delete(storage_path('app/private/'.$storedAttachment['path']));
            }

            throw $exception;
        }

        return redirect()
            ->route('school-teacher-attendance.excuses.index', [
                'institution' => $institution,
                'date' => $data['attendance_date'],
            ])
            ->with('success', 'Status '.strtoupper($data['status']).' berhasil dicatat untuk '.$teacher->name.'.');
    }

    public function download(
        Institution $institution,
        SchoolTeacherAttendanceExcuse $excuse
    ): BinaryFileResponse {
        $this->authorizeInstitution($institution);
        $this->authorizeExcuse($institution, $excuse);

        abort_unless($excuse->attachment_path, 404, 'Lampiran tidak tersedia.');

        $path = storage_path('app/private/'.$excuse->attachment_path);
        abort_unless(File::exists($path), 404, 'File lampiran tidak ditemukan.');

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $teacherName = $excuse->teacher?->name ?? 'guru';
        $filename = Str::slug(
            'surat-'.$excuse->status.'-'.$teacherName.'-'.$excuse->attendance_date->format('Y-m-d')
        ).'.'.$extension;

        return response()->download($path, $filename);
    }

    public function destroy(
        Institution $institution,
        SchoolTeacherAttendanceExcuse $excuse
    ): RedirectResponse {
        $this->authorizeInstitution($institution);
        $this->authorizeExcuse($institution, $excuse);

        $date = $excuse->attendance_date->toDateString();
        $attachmentPath = $excuse->attachment_path;

        $excuse->delete();

        if ($attachmentPath) {
            File::delete(storage_path('app/private/'.$attachmentPath));
        }

        return redirect()
            ->route('school-teacher-attendance.excuses.index', [
                'institution' => $institution,
                'date' => $date,
            ])
            ->with('success', 'Status izin/sakit guru berhasil dihapus.');
    }

    private function storeAttachment(UploadedFile $file): array
    {
        $directory = storage_path('app/private/school-teacher-attendance/letters');
        File::ensureDirectoryExists($directory, 0755, true);

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid().'.'.$extension;
        $relativePath = 'school-teacher-attendance/letters/'.$filename;
        $metadata = [
            'path' => $relativePath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];

        $file->move($directory, $filename);

        return $metadata;
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

    private function authorizeExcuse(
        Institution $institution,
        SchoolTeacherAttendanceExcuse $excuse
    ): void {
        abort_unless($excuse->institution_id === $institution->id, 404);
    }

    private function currentAcademicYear(): string
    {
        return now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
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
}
