<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $kelas = $request->query('kelas');
        $kamar = $request->query('kamar');

        $students = Student::query()
            ->when($q, fn($qr) => $qr->where(function($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                  ->orWhere('nis', 'like', "%$q%");
            }))
            ->when($kelas, fn($qr) => $qr->where('kelas', $kelas))
            ->when($kamar, fn($qr) => $qr->where('kamar', $kamar))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $kelasList = Student::query()->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $kamarList = Student::query()->whereNotNull('kamar')->distinct()->orderBy('kamar')->pluck('kamar');

        return view('students.index', compact('students','q','kelas','kamar','kelasList','kamarList'));
    }

    public function create()
    {
        return view('students.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nis'       => ['required','string','max:50','unique:students,nis'],
            'name'      => ['required','string','max:120'],
            'kelas'     => ['nullable','string','max:50'],
            'kamar'     => ['nullable','string','max:50'],
            'is_active' => ['nullable','boolean'],
            'photo'     => ['nullable','image','mimes:jpg,jpeg,png','max:2048'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        if ($request->hasFile('photo')) {
            $dir = public_path('uploads/students');

            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $file->move($dir, $filename);

            $data['photo'] = 'uploads/students/' . $filename;
        }

        $student = Student::create($data);

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Santri berhasil ditambahkan.');
    }

    public function show(Student $student)
    {
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'nis'       => ['required','string','max:50', Rule::unique('students','nis')->ignore($student->id)],
            'name'      => ['required','string','max:120'],
            'kelas'     => ['nullable','string','max:50'],
            'kamar'     => ['nullable','string','max:50'],
            'is_active' => ['nullable','boolean'],
            'photo'     => ['nullable','image','mimes:jpg,jpeg,png','max:2048'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if ($request->hasFile('photo')) {
            if ($student->photo && file_exists(public_path($student->photo))) {
                unlink(public_path($student->photo));
            }

            $dir = public_path('uploads/students');

            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $file->move($dir, $filename);

            $data['photo'] = 'uploads/students/' . $filename;
        }

        $student->update($data);

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Data santri berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        if ($student->photo && file_exists(public_path($student->photo))) {
            unlink(public_path($student->photo));
        }

        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', 'Santri berhasil dihapus.');
    }
}