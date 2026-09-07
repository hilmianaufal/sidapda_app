<?php

namespace App\Exports;

use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        public int $institutionId,
        public string $academicYear,
        public ?string $className = null,
    ) {}

    public function collection(): Collection
    {
        return StudentEnrollment::query()
            ->with(['student', 'institution'])
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.institution_id', $this->institutionId)
            ->where('student_enrollments.academic_year', $this->academicYear)
            ->where('student_enrollments.is_active', true)
            ->where('students.is_active', true)
            ->when($this->className, fn ($query) => $query->where('student_enrollments.class_name', $this->className))
            ->select('student_enrollments.*')
            ->orderBy('student_enrollments.class_name')
            ->orderBy('students.name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'NIS',
            'Nama',
            'Jenis Santri',
            'Status Mukim',
            'Lembaga',
            'Tahun Ajaran',
            'Kelas Lembaga',
            'Jenjang Lembaga',
            'Kamar',
            'WhatsApp Orang Tua',
        ];
    }

    public function map($enrollment): array
    {
        $student = $enrollment->student;

        return [
            $student->nis,
            $student->name,
            $student->gender === 'putra' ? 'Putra' : ($student->gender === 'putri' ? 'Putri' : '-'),
            $student->residency_status === 'non_mukim' ? 'Tidak Mukim' : 'Mukim',
            $enrollment->institution->short_name,
            $enrollment->academic_year,
            $enrollment->class_name ?: '-',
            $enrollment->level ?: '-',
            $student->kamar ?: '-',
            $student->parent_phone ?: '-',
        ];
    }
}
