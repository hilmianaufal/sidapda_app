<?php

namespace App\Exports;

use App\Models\Institution;
use App\Services\SchoolTeacherAttendanceReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SchoolTeacherAttendanceDailyExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    public function __construct(
        private readonly int $institutionId,
        private readonly string $date,
        private readonly ?string $level = null
    ) {}

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Kode Guru',
            'Nama Guru',
            'Jenis Kelamin',
            'Jenjang',
            'Status Harian',
            'Jam Masuk',
            'Status Masuk',
            'Jarak Masuk (m)',
            'Akurasi Masuk (m)',
            'Jam Pulang',
            'Status Pulang',
            'Jarak Pulang (m)',
            'Akurasi Pulang (m)',
            'Keterangan',
        ];
    }

    public function array(): array
    {
        $institution = Institution::query()->findOrFail($this->institutionId);
        $report = app(SchoolTeacherAttendanceReportService::class)->build(
            $institution,
            $this->date,
            $this->level
        );

        return $report['rows']
            ->values()
            ->map(fn (array $row, int $index) => [
                $index + 1,
                $this->date,
                $row['teacher_code'],
                $row['name'],
                $row['gender'],
                $row['level_label'],
                $row['day_status_label'],
                $row['check_in_time'] ?: '-',
                $row['check_in_status_label'],
                $row['check_in_distance_meters'] ?? '-',
                $row['check_in_accuracy_meters'] ?? '-',
                $row['check_out_time'] ?: '-',
                $row['check_out_status_label'],
                $row['check_out_distance_meters'] ?? '-',
                $row['check_out_accuracy_meters'] ?? '-',
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
