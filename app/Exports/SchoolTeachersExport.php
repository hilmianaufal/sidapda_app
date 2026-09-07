<?php

namespace App\Exports;

use App\Models\SchoolTeacher;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SchoolTeachersExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping
{
    public function __construct(
        private readonly int $institutionId,
        private readonly ?string $search = null,
        private readonly ?string $level = null,
        private readonly ?string $status = null
    ) {}

    public function collection(): Collection
    {
        return SchoolTeacher::query()
            ->where('institution_id', $this->institutionId)
            ->when($this->search, function ($query) {
                $query->where(function ($item) {
                    $item->where('name', 'like', "%{$this->search}%")
                        ->orWhere('teacher_code', 'like', "%{$this->search}%");
                });
            })
            ->when($this->level, fn ($query) => $query->where('level', $this->level))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Kode Guru/NIP',
            'Nama Guru',
            'Jenis Kelamin',
            'Jenjang',
            'No. HP',
            'Status',
        ];
    }

    public function map($teacher): array
    {
        return [
            $teacher->teacher_code,
            $teacher->name,
            $teacher->genderLabel(),
            $teacher->levelLabel(),
            $teacher->phone ?: '-',
            $teacher->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
