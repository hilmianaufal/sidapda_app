<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SchoolAttendance;
use App\Models\SchoolAttendanceExcuse;
use App\Models\StudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\StudentWhatsappNotifier;

class SchoolAttendanceExcuseController extends Controller
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
        $className = filled($request->query('class_name'))
            ? trim((string) $request->query('class_name'))
            : null;
        $q = filled($request->query('q'))
            ? trim((string) $request->query('q'))
            : null;

        $enrollments = StudentEnrollment::query()
            ->with('student')
            ->where('institution_id', $institution->id)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->get();

        $studentOptions = $enrollments
            ->sortBy(fn ($enrollment) => strtolower($enrollment->student?->name ?? ''))
            ->values();

        $classOptions = $enrollments
            ->pluck('class_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $baseQuery = SchoolAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->whereDate('attendance_date', $date);

        $totals = [
            'izin' => (clone $baseQuery)->where('status', 'izin')->count(),
            'sakit' => (clone $baseQuery)->where('status', 'sakit')->count(),
            'lainnya' => (clone $baseQuery)->where('status', 'lainnya')->count(),
        ];

        $excuses = (clone $baseQuery)
            ->with(['student', 'recorder'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($className, fn ($query) => $query->where('class_name_snapshot', $className))
            ->when($q, function ($query) use ($q) {
                $query->whereHas('student', function ($student) use ($q) {
                    $student->where('name', 'like', "%{$q}%")
                        ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('school-attendance.excuses', compact(
            'institution',
            'academicYear',
            'date',
            'status',
            'className',
            'q',
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

        $hasAttendance = SchoolAttendance::query()
            ->where('institution_id', $institution->id)
            ->where('student_id', $data['student_id'])
            ->whereDate('attendance_date', $data['attendance_date'])
            ->where(function ($query) {
                $query->whereNotNull('check_in_at')
                    ->orWhereNotNull('check_out_at');
            })
            ->exists();

        if ($hasAttendance) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'Siswa sudah memiliki scan masuk/pulang pada tanggal tersebut.']);
        }

        $alreadyExists = SchoolAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->where('student_id', $data['student_id'])
            ->whereDate('attendance_date', $data['attendance_date'])
            ->exists();

        if ($alreadyExists) {
            return back()
                ->withInput()
                ->withErrors(['student_id' => 'Status izin/sakit siswa pada tanggal tersebut sudah tercatat.']);
        }

        $attachment = $request->file('attachment');
        $storedAttachment = $attachment ? $this->storeAttachment($attachment) : null;

        try {
            SchoolAttendanceExcuse::create([
                'institution_id' => $institution->id,
                'student_id' => $data['student_id'],
                'attendance_date' => $data['attendance_date'],
                'academic_year' => $academicYear,
                'class_name_snapshot' => $enrollment->class_name,
                'level_snapshot' => $enrollment->level,
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

        $whatsapp->excuse(
            $institution,
            $enrollment->student,
            'Absensi '.$institution->short_name,
            $data['status'],
            $data['attendance_date'],
            $data['notes'] ?? null
        );

        return redirect()
            ->route('school-attendance.excuses.index', [
                'institution' => $institution,
                'date' => $data['attendance_date'],
            ])
            ->with('success', 'Status '.strtoupper($data['status']).' berhasil dicatat untuk '.$enrollment->student->name.'.');
    }

    public function download(
        Institution $institution,
        SchoolAttendanceExcuse $excuse
    ): BinaryFileResponse {
        $this->authorizeInstitution($institution);
        $this->authorizeExcuse($institution, $excuse);

        abort_unless($excuse->attachment_path, 404, 'Lampiran tidak tersedia.');

        $path = storage_path('app/private/'.$excuse->attachment_path);
        abort_unless(File::exists($path), 404, 'File lampiran tidak ditemukan.');

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $studentName = $excuse->student?->name ?? 'siswa';
        $filename = Str::slug(
            'surat-'.$excuse->status.'-'.$studentName.'-'.$excuse->attendance_date->format('Y-m-d')
        ).'.'.$extension;

        return response()->download($path, $filename);
    }

    public function destroy(
        Institution $institution,
        SchoolAttendanceExcuse $excuse
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
            ->route('school-attendance.excuses.index', [
                'institution' => $institution,
                'date' => $date,
            ])
            ->with('success', 'Status izin/sakit berhasil dihapus.');
    }

    private function storeAttachment(UploadedFile $file): array
    {
        $directory = storage_path('app/private/school-attendance/letters');
        File::ensureDirectoryExists($directory, 0755, true);

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid().'.'.$extension;
        $relativePath = 'school-attendance/letters/'.$filename;
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
        SchoolAttendanceExcuse $excuse
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
