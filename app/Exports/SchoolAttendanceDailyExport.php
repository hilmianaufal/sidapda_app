<?php

namespace App\Exports;

use App\Models\Institution;
use App\Services\SchoolAttendanceReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SchoolAttendanceDailyExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    public function __construct(
        private readonly int $institutionId,
        private readonly string $date,
        private readonly string $academicYear,
        private readonly ?string $level = null,
        private readonly ?string $className = null
    ) {}

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'NIS',
            'Nama',
            'Jenis Kelamin',
            'Jenjang',
            'Kelas',
            'Status Harian',
            'Jam Masuk',
            'Status Masuk',
            'Jam Pulang',
            'Status Pulang',
            'Keterangan',
        ];
    }

    public function array(): array
    {
        $institution = Institution::query()->findOrFail($this->institutionId);
        $report = app(SchoolAttendanceReportService::class)->build(
            $institution,
            $this->date,
            $this->academicYear,
            $this->level,
            $this->className
        );

        return $report['rows']
            ->values()
            ->map(fn (array $row, int $index) => [
                $index + 1,
                $this->date,
                $row['nis'],
                $row['name'],
                $row['gender'],
                $row['level'],
                $row['class_name'],
                $row['day_status_label'],
                $row['check_in_time'] ?: '-',
                $row['check_in_status_label'],
                $row['check_out_time'] ?: '-',
                $row['check_out_status_label'],
                $row['notes'],
            ])
            ->all();
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'DCFCE7'],
                ],
            ],
        ];
    }
}
