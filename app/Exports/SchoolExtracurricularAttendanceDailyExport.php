<?php

namespace App\Exports;

use App\Models\Institution;
use App\Models\SchoolExtracurricular;
use App\Services\SchoolExtracurricularAttendanceReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SchoolExtracurricularAttendanceDailyExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles
{
    public function __construct(
        private readonly int $institutionId,
        private readonly int $extracurricularId,
        private readonly string $date,
        private readonly string $academicYear,
        private readonly ?string $className = null
    ) {}

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Ekstrakurikuler',
            'Jadwal',
            'NIS',
            'Nama',
            'Jenis Kelamin',
            'Jenjang',
            'Kelas',
            'Status',
            'Jam Scan',
            'Keterangan',
        ];
    }

    public function array(): array
    {
        $institution = Institution::query()->findOrFail($this->institutionId);
        $extracurricular = SchoolExtracurricular::query()
            ->where('institution_id', $institution->id)
            ->findOrFail($this->extracurricularId);
        $report = app(SchoolExtracurricularAttendanceReportService::class)->build(
            $institution,
            $extracurricular,
            $this->date,
            $this->academicYear,
            $this->className
        );
        $schedule = $extracurricular->dayLabel().' '
            .$extracurricular->startTimeLabel().'–'.$extracurricular->endTimeLabel();

        return $report['rows']
            ->values()
            ->map(fn (array $row, int $index) => [
                $index + 1,
                $this->date,
                $extracurricular->name,
                $schedule,
                $row['nis'],
                $row['name'],
                $row['gender'],
                $row['level_label'],
                $row['class_name'],
                $row['day_status_label'],
                $row['scan_time'] ?: '-',
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
