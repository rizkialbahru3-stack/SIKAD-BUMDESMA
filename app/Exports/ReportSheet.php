<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Satu sheet laporan dengan kop judul + header berwarna.
 *
 * Layout: baris 1 kop instansi (pita warna), baris 2 judul + periode,
 * baris 3 waktu ekspor, baris 4 kosong, baris 5 heading,
 * baris 6 dst. data, terakhir opsional baris total. Kolom pertama "No".
 */
class ReportSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithTitle
{
    public const HEADING_ROW = 5;

    public function __construct(
        private string $sheetTitle,
        private string $reportTitle,
        private string $periodLabel,
        private array $headings,
        private array $rows,
        private array $columnFormats = [],
        private string $accent = '1E5AA8',
        private ?array $footerRow = null,
        private ?string $percentColumn = null,
    ) {}

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }

    public function array(): array
    {
        return [
            ['BUMDESMA LKD TARUB'],
            [mb_strtoupper($this->reportTitle).' — Periode '.$this->periodLabel],
            ['Diekspor: '.now()->format('d/m/Y H:i')],
            [''],
            $this->headings,
            ...$this->rows,
            ...($this->footerRow === null ? [] : [$this->footerRow]),
        ];
    }

    public function columnFormats(): array
    {
        return $this->columnFormats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(count($this->headings));
                $firstDataRow = self::HEADING_ROW + 1;
                $lastDataRow = self::HEADING_ROW + count($this->rows);
                $lastRow = $lastDataRow + ($this->footerRow === null ? 0 : 1);
                $headingRange = 'A'.self::HEADING_ROW.':'.$lastCol.self::HEADING_ROW;
                $tableRange = 'A'.self::HEADING_ROW.':'.$lastCol.$lastRow;
                $hasData = count($this->rows) > 0;

                // Pita judul.
                $sheet->mergeCells('A1:'.$lastCol.'1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($this->accent);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('334155');
                $sheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('64748B');

                // Heading.
                $sheet->getRowDimension(self::HEADING_ROW)->setRowHeight(22);
                $sheet->getStyle($headingRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle($headingRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($this->accent);
                $sheet->getStyle($headingRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Border + zebra + perataan.
                $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D7E2EB');
                if ($hasData) {
                    for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                        if (($row - $firstDataRow) % 2 === 1) {
                            $sheet->getStyle('A'.$row.':'.$lastCol.$row)->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F6FC');
                        }
                    }
                    $sheet->getStyle('A'.$firstDataRow.':A'.$lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    foreach (array_keys($this->columnFormats) as $col) {
                        $sheet->getStyle($col.$firstDataRow.':'.$col.$lastDataRow)
                            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                // Baris total.
                if ($this->footerRow !== null) {
                    $footerRange = 'A'.$lastRow.':'.$lastCol.$lastRow;
                    $sheet->getStyle($footerRange)->getFont()->setBold(true);
                    $sheet->getStyle($footerRange)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
                    $sheet->getStyle($footerRange)->getBorders()->getTop()
                        ->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB($this->accent);
                }

                // Sorotan status + ambang persentase.
                if ($hasData) {
                    $dataRange = 'B'.$firstDataRow.':'.$lastCol.$lastDataRow;
                    $sheet->getStyle($dataRange)->setConditionalStyles($this->statusRules());
                    if ($this->percentColumn !== null) {
                        $pctRange = $this->percentColumn.$firstDataRow.':'.$this->percentColumn.$lastDataRow;
                        $sheet->getStyle($pctRange)->setConditionalStyles($this->percentRules());
                    }
                }

                // Tab sheet + cetak.
                $sheet->getTabColor()->setRGB($this->accent);
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(self::HEADING_ROW, self::HEADING_ROW);
                $sheet->getHeaderFooter()->setOddFooter('&L BUMDESMA LKD TARUB · '.$this->sheetTitle.'&R Halaman &P dari &N');

                $sheet->freezePane('A'.$firstDataRow);
                $sheet->setAutoFilter($headingRange);
            },
        ];
    }

    /**
     * Warna teks status ("Disetujui" hijau, "Ditolak" merah, ...).
     * "Nonaktif" diprioritaskan + stop agar tak tertimpa aturan "Aktif".
     */
    private function statusRules(): array
    {
        $inactive = $this->textRule('Nonaktif', '475569', 'E2E8F0');
        $inactive->setStopIfTrue(true);

        return [
            $inactive,
            $this->textRule('Disetujui', '065F46', 'D1FAE5'),
            $this->textRule('Dibayar', '065F46', 'D1FAE5'),
            $this->textRule('Aktif', '065F46', 'D1FAE5'),
            $this->textRule('Ditolak', '991B1B', 'FEE2E2'),
            $this->textRule('Menunggu', '92400E', 'FEF3C7'),
            $this->textRule('Draft', '92400E', 'FEF3C7'),
        ];
    }

    private function textRule(string $text, string $fontRgb, string $fillRgb): Conditional
    {
        $rule = new Conditional;
        $rule->setConditionType(Conditional::CONDITION_CONTAINSTEXT);
        $rule->setOperatorType(Conditional::OPERATOR_CONTAINSTEXT);
        $rule->setText($text);
        $rule->getStyle()->getFont()->setBold(true)->getColor()->setRGB($fontRgb);
        $rule->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fillRgb);

        return $rule;
    }

    /**
     * Lampu lalu lintas untuk kolom fraksi 0–1 (≥80% hijau, <50% merah).
     */
    private function percentRules(): array
    {
        $good = new Conditional;
        $good->setConditionType(Conditional::CONDITION_CELLIS);
        $good->setOperatorType(Conditional::OPERATOR_GREATERTHANOREQUAL);
        $good->addCondition(0.8);
        $good->getStyle()->getFont()->setBold(true)->getColor()->setRGB('065F46');
        $good->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1FAE5');

        $mid = new Conditional;
        $mid->setConditionType(Conditional::CONDITION_CELLIS);
        $mid->setOperatorType(Conditional::OPERATOR_BETWEEN);
        $mid->addCondition(0.5);
        $mid->addCondition(0.8);
        $mid->getStyle()->getFont()->setBold(true)->getColor()->setRGB('92400E');
        $mid->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');

        $bad = new Conditional;
        $bad->setConditionType(Conditional::CONDITION_CELLIS);
        $bad->setOperatorType(Conditional::OPERATOR_LESSTHAN);
        $bad->addCondition(0.5);
        $bad->getStyle()->getFont()->setBold(true)->getColor()->setRGB('991B1B');
        $bad->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');

        return [$good, $mid, $bad];
    }
}
