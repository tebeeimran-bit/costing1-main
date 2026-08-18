<?php

namespace App\Services\ControlProject;

use App\Models\ProjectA00Form;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class A00ExcelPdfService
{
    private const COLUMN_SCALE = 0.97;
    private const FONT_SCALE = 0.72;
    private const DRAWING_SCALE = 0.82;

    public function generate(ProjectA00Form $a00): string
    {
        $template = resource_path('a00-new-project-template.xlsx');
        abort_unless(is_file($template), 500, 'Template Excel A00 tidak ditemukan.');
        $a00->loadMissing('items');

        $book = IOFactory::load($template);
        $this->fillMain($book->getSheetByName('A00') ?? $book->getSheet(0), $a00, true);
        $this->fillAttachment($book, $a00);
        if ($attachment = $book->getSheetByName('LAMPIRAN ASSY')) {
            $this->prepareAttachmentForPdf($attachment);
        }
        foreach ($book->getWorksheetIterator() as $sheet) $this->normalizeForA4($sheet);

        File::ensureDirectoryExists(storage_path('app/temp'));
        $path = tempnam(storage_path('app/temp'), 'a00-').'.pdf';
        $writer = IOFactory::createWriter($book, 'Dompdf');
        $writer->writeAllSheets();
        $writer->save($path);

        return $path;
    }

    public function generateExcel(ProjectA00Form $a00): string
    {
        $template = resource_path('a00-new-project-template.xlsx');
        abort_unless(is_file($template), 500, 'Template Excel A00 tidak ditemukan.');
        $a00->loadMissing('items');

        $book = IOFactory::load($template);
        $this->fillMain($book->getSheetByName('A00') ?? $book->getSheet(0), $a00, false);
        $this->fillAttachment($book, $a00);

        File::ensureDirectoryExists(storage_path('app/temp'));
        $path = tempnam(storage_path('app/temp'), 'a00-').'.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);

        return $path;
    }

    private function fillMain(Worksheet $sheet, ProjectA00Form $a00, bool $translateForPdf): void
    {
        if ($translateForPdf) $this->translateMainMergedCells($sheet);
        $this->replaceAndCenterLogo($sheet);
        $item = $a00->items->first();
        $bulky = $a00->items->count() > 1;
        $regularLives = $a00->items->where('spot_order', false)
            ->pluck('product_life_years')->filter(fn ($years) => $years !== null)
            ->unique()->sort()->values();
        $hasRegularOrder = $a00->items->contains(fn ($row) => !$row->spot_order);
        $hasSpotOrder = $a00->items->contains(fn ($row) => (bool) $row->spot_order);
        $lifeText = $regularLives->map(fn ($years) => $years.' Years')->join(', ');
        $values = [
            'S2'=>$a00->document_number, 'S3'=>\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($a00->document_date), 'S4'=>$a00->revision,
            'S5'=>$a00->from_department.' - '.$a00->to_department, 'K11'=>$a00->formattedCustomerName(),
            'K12'=>$bulky?$a00->items->pluck('model')->filter()->unique()->join(', '):$item?->model,
            'K13'=>$bulky?$a00->items->pluck('assy_name')->filter()->unique()->join(', '):$item?->assy_name,
            'K14'=>$bulky?'Terlampir':$item?->assy_number, 'K15'=>$bulky?'Terlampir':$item?->quantity,
            'N15'=>$bulky?'':$item?->quantity_uom, 'P15'=>$bulky?'':'per',
            'Q15'=>$bulky?'':preg_replace('/^per\s+/i','',(string)$item?->quantity_basis),
            'L16'=>$bulky?$lifeText:($item?->spot_order?'':($item?->product_life_years!==null?$item->product_life_years.' Years':'')),
        ];
        foreach ($values as $cell=>$value) $sheet->setCellValue($cell,$value);
        $sheet->getStyle('S3')->getNumberFormat()->setFormatCode('dd-mmm-yy');
        $sheet->setCellValue('K16',$hasRegularOrder?'☑':'☐');
        $sheet->setCellValue('K17',$hasSpotOrder?'☑':'☐');
        $sheet->getStyle('K16:K17')->getFont()->setName('DejaVu Sans')->setSize(12)->setBold(true);
        $sheet->getStyle('K16:K17')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        foreach ([21=>$a00->due_part_list,22=>$a00->due_umh,23=>$a00->due_new_part_price,24=>$a00->due_costing,25=>$a00->due_submit_quotation] as $row=>$date) {
            $this->dateParts($sheet,$row,$date);
        }

        $events=collect($a00->resolvedCustomerEvents());
        if($events->count()>5){
            $extra=$events->count()-5;$sheet->insertNewRowBefore(34,$extra);
            for($row=34;$row<34+$extra;$row++){
                $sheet->duplicateStyle($sheet->getStyle('D33:S33'),"D{$row}:S{$row}");
                $sheet->mergeCells("E{$row}:J{$row}");$sheet->mergeCells("L{$row}:M{$row}");$sheet->mergeCells("N{$row}:O{$row}");$sheet->mergeCells("P{$row}:Q{$row}");
                $sheet->getStyle("E{$row}:J{$row}")->getAlignment()->setWrapText(false);
                $sheet->getRowDimension($row)->setRowHeight(13);
            }
        }
        $rows=max(5,$events->count());
        for($i=0;$i<$rows;$i++){
            $row=29+$i;$event=$events->get($i);$sheet->getRowDimension($row)->setVisible(true);
            foreach(['D','E','K','L','N','P','S'] as $column)$sheet->setCellValue($column.$row,null);
            $sheet->duplicateStyle($sheet->getStyle('D21'), 'D'.$row);
            $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal($sheet->getStyle('D21')->getAlignment()->getHorizontal());
            if(!$event){
                $sheet->getStyle("L{$row}:Q{$row}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
                continue;
            }
            $sheet->setCellValueExplicit('D'.$row,($i+1).'.',\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);$sheet->setCellValue('E'.$row,$event['name']);
            $sheet->setCellValue('K'.$row,':');$sheet->setCellValue('S'.$row,'(dd/mmm/yyyy)');
            $this->dateParts($sheet,$row,$event['date']??null,(bool)($event['tba']??false));
        }
        $offset=max(0,$events->count()-5);
        $issueCell=($translateForPdf?'M':'S').(43+$offset);
        $sheet->setCellValue($issueCell,$a00->issue_location.', '.$a00->document_date->locale('id')->translatedFormat('d F Y'));
        $issueRow=43+$offset;
        $issueRange=($translateForPdf?'M':'S').$issueRow.':V'.$issueRow;
        $sheet->getStyle($issueRange)->getBorders()->getBottom()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
        $sheet->getStyle($issueCell)->getFont()
            ->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE);
        if(!$translateForPdf)$sheet->setCellValue('M'.(43+$offset),null);
        $sheet->setCellValue('M'.(49+$offset),$a00->approved_by?:'-');
        $sheet->setCellValue('Q'.(49+$offset),$a00->acknowledged_by?:'-');
        $preparedBy=trim((string)$a00->prepared_by);
        $sheet->setCellValue('T'.(49+$offset),preg_match('/^admin(istrator)?$/i',$preparedBy)?'':($preparedBy?:'-'));
        $sheet->setCellValue('M'.(50+$offset),$a00->resolvedSignerRoleLabel('approved'));
        $sheet->setCellValue('Q'.(50+$offset),$a00->resolvedSignerRoleLabel('acknowledged'));
        $sheet->setCellValue('T'.(50+$offset),$a00->resolvedSignerRoleLabel('prepared'));
        foreach($sheet->getDrawingCollection() as $key=>$drawing)if(stripos($drawing->getName(),'LOGO')===false)$sheet->getDrawingCollection()->offsetUnset($key);
        $this->addApprovalSignatures($sheet,$a00,$offset);
        $sheet->getPageSetup()->setPrintArea('A1:W'.(55+$offset));
    }

    private function addApprovalSignatures(Worksheet $sheet, ProjectA00Form $a00, int $offset): void
    {
        $approvedSignature=$a00->resolvedSignaturePath('approved');
        $acknowledgedSignature=$a00->resolvedSignaturePath('acknowledged');
        $preparedSignature=$a00->resolvedSignaturePath('prepared');
        $placements = [
            [$approvedSignature, 'M'.(46+$offset), 'TTD Disetujui'],
            [$acknowledgedSignature, 'Q'.(46+$offset), 'TTD Diketahui'],
            [$preparedSignature, 'T'.(46+$offset), 'TTD Dibuat'],
        ];

        foreach ($placements as [$storedPath,$coordinate,$name]) {
            if (!$storedPath) continue;
            $path=\Illuminate\Support\Facades\Storage::disk('public')->path($storedPath);
            if (!is_file($path)) continue;
            $drawing=new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName($name)->setDescription($name)->setPath($path)->setCoordinates($coordinate);
            $drawing->setHeight(58)->setWorksheet($sheet);
            $this->centerSignatureInApprovalBox($sheet,$drawing);
        }
    }


    private function centerSignatureInApprovalBox(Worksheet $sheet, mixed $drawing): void
    {
        $startColumn=preg_replace('/\d+/','',$drawing->getCoordinates());
        $startRow=(int)preg_replace('/\D+/','',$drawing->getCoordinates());
        $endColumn=match($startColumn){'M'=>'P','Q'=>'S','T'=>'V',default=>$startColumn};
        $font=$sheet->getParent()->getDefaultStyle()->getFont();
        $boxWidth=0;
        $from=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startColumn);
        $to=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($endColumn);
        for($index=$from;$index<=$to;$index++){
            $column=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
            $boxWidth+=\PhpOffice\PhpSpreadsheet\Shared\Drawing::cellDimensionToPixels($sheet->getColumnDimension($column)->getWidth(),$font);
        }
        $boxHeight=0;
        foreach(range($startRow,$startRow+2) as $row){
            $height=$sheet->getRowDimension($row)->getRowHeight();
            if($height<0)$height=$sheet->getDefaultRowDimension()->getRowHeight();
            $boxHeight+=\PhpOffice\PhpSpreadsheet\Shared\Drawing::pointsToPixels($height);
        }
        $drawing->setOffsetX(max(0,(int)round(($boxWidth-$drawing->getWidth())/2)));
        $drawing->setOffsetY(max(0,(int)round(($boxHeight-$drawing->getHeight())/2)));
    }

    private function translateMainMergedCells(Worksheet $sheet): void
    {
        foreach (['B7:V7','D9:J9','D19:J19','D27:J27','M43:V43','A52:V52','A53:V53','A54:V54','A55:V55'] as $range) $sheet->mergeCells($range);
        foreach (range(11,16) as $row) $sheet->mergeCells("E{$row}:I{$row}");
        foreach (range(11,14) as $row) $sheet->mergeCells("K{$row}:Q{$row}");
        foreach (range(21,25) as $row) $sheet->mergeCells("E{$row}:J{$row}");
        foreach (range(29,33) as $row) $sheet->mergeCells("E{$row}:J{$row}");
        $sheet->mergeCells('L16:Q16');
        $sheet->mergeCells('L17:Q17');

        $sheet->getStyle('A1:W55')->getAlignment()->setWrapText(false);
        $sheet->getStyle('B7:V7')->getAlignment()->setWrapText(false)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D9:J9')->getAlignment()->setWrapText(false);
        $sheet->getStyle('D19:J19')->getAlignment()->setWrapText(false);
        $sheet->getStyle('D27:J27')->getAlignment()->setWrapText(false);
        $sheet->getStyle('E11:Q17')->getAlignment()->setWrapText(false)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E21:J25')->getAlignment()->setWrapText(false)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E29:J33')->getAlignment()->setWrapText(false)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('M43:V43')->getAlignment()->setWrapText(false)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A52:V55')->getAlignment()->setWrapText(false)->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        foreach ([2,3,4,5] as $row) $sheet->getRowDimension($row)->setRowHeight(12);
        foreach ([11,12,13,14,15,16,17,21,22,23,24,25,29,30,31,32,33,43] as $row) $sheet->getRowDimension($row)->setRowHeight(13);
        foreach ([9,19,27] as $row) $sheet->getRowDimension($row)->setRowHeight(14);
        foreach ([34,35,36,37,38,39,40,41,42] as $row) $sheet->getRowDimension($row)->setRowHeight(16);
        foreach ([1,6,8,10,18,20,26,28,44,51] as $row) $sheet->getRowDimension($row)->setRowHeight(8);
        foreach ([45,46,47,48,49,50] as $row) $sheet->getRowDimension($row)->setRowHeight(12);
        foreach ([52,53,54,55] as $row) $sheet->getRowDimension($row)->setRowHeight(10);
    }

    private function fillAttachment(Spreadsheet $book, ProjectA00Form $a00): void
    {
        $sheet=$book->getSheetByName('LAMPIRAN ASSY');if(!$sheet)return;
        if($a00->items->count()<=1){$book->removeSheetByIndex($book->getIndex($sheet));return;}
        $sheet->setCellValue('K9',$a00->formattedCustomerName());$sheet->setCellValue('K10',$a00->items->pluck('model')->filter()->unique()->join(', '));
        if($a00->items->count()>3){$extra=$a00->items->count()-3;$sheet->insertNewRowBefore(17,$extra);for($row=17;$row<17+$extra;$row++){$sheet->duplicateStyle($sheet->getStyle('D16:R16'),"D{$row}:R{$row}");$sheet->mergeCells("D{$row}:H{$row}");$sheet->mergeCells("I{$row}:M{$row}");$sheet->mergeCells("N{$row}:R{$row}");}}
        foreach($a00->items->values() as $i=>$item){$row=14+$i;$monthly=$item->quantity;if($monthly!==null&&str_contains(strtolower((string)$item->quantity_basis),'year'))$monthly/=12;$sheet->setCellValue('D'.$row,$item->assy_name);$sheet->setCellValue('I'.$row,$item->assy_number);$sheet->setCellValue('N'.$row,$monthly);}
        for($i=$a00->items->count();$i<3;$i++)$sheet->getRowDimension(14+$i)->setVisible(false);
        $sheet->getPageSetup()->setPrintArea('A1:R54');
    }

    private function prepareAttachmentForPdf(Worksheet $sheet): void
    {
        // Dompdf tidak selalu mempertahankan lebar visual cell Excel yang
        // tidak di-merge. Gabungkan area nilai agar Customer dan Model tetap
        // satu baris serta matikan wrap pada seluruh halaman lampiran.
        foreach (['D7:J7', 'K9:R9', 'K10:R10'] as $range) {
            if (!isset($sheet->getMergeCells()[$range])) {
                $sheet->mergeCells($range);
            }
        }
        $sheet->getStyle('A1:W54')->getAlignment()->setWrapText(false);
        $sheet->getStyle('D7:J7')->getAlignment()
            ->setWrapText(false)
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle('K9:R10')->getAlignment()
            ->setWrapText(false)
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // Beri napas vertikal seperti layout Excel asli tanpa mengubah teks,
        // font, ataupun susunan kolomnya.
        foreach ([7 => 18, 8 => 12, 9 => 18, 10 => 18, 11 => 12, 12 => 12] as $row => $height) {
            $sheet->getRowDimension($row)->setRowHeight($height);
        }
        foreach (range(13, 16) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // Baris kosong harus tetap memiliki tinggi fisik agar bingkai luar
        // tidak berhenti di tengah halaman ketika dirender menjadi PDF.
        foreach (range(17, 49) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(15);
        }

        $border = ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']];
        $sheet->getStyle('A1:W54')->applyFromArray([
            'borders' => [
                'outline' => $border,
            ],
        ]);
        $sheet->getPageSetup()->setPrintArea('A1:W54');
    }

    private function replaceAndCenterLogo(Worksheet $sheet): void
    {
        $logoPath = public_path('images/logo-dharma-mark.png');
        if (!is_file($logoPath)) {
            return;
        }

        foreach ($sheet->getDrawingCollection() as $drawing) {
            if (stripos($drawing->getName(), 'LOGO') === false) {
                continue;
            }

            $drawing->setPath($logoPath);
            $drawing->setCoordinates('B2');
            $drawing->setHeight(48);
            $this->centerLogoInHeader($sheet, $drawing);
        }
    }

    private function centerLogoInHeader(Worksheet $sheet, mixed $drawing): void
    {
        $font = $sheet->getParent()->getDefaultStyle()->getFont();
        $boxWidth = 0;
        foreach (range('B', 'E') as $column) {
            $boxWidth += \PhpOffice\PhpSpreadsheet\Shared\Drawing::cellDimensionToPixels(
                $sheet->getColumnDimension($column)->getWidth(),
                $font
            );
        }

        $boxHeight = 0;
        foreach (range(2, 5) as $row) {
            $height = $sheet->getRowDimension($row)->getRowHeight();
            if ($height < 0) {
                $height = $sheet->getDefaultRowDimension()->getRowHeight();
            }
            $boxHeight += \PhpOffice\PhpSpreadsheet\Shared\Drawing::pointsToPixels($height);
        }

        $drawing->setOffsetX(max(0, (int) round(($boxWidth - $drawing->getWidth()) / 2)));
        $drawing->setOffsetY(max(0, (int) round(($boxHeight - $drawing->getHeight()) / 2)));
    }

    private function dateParts(Worksheet $sheet,int $row,mixed $value,bool $tba=false): void
    {
        $date=filled($value)?\Carbon\Carbon::parse($value):null;
        $sheet->setCellValue('L'.$row,$tba?'TBA':$date?->format('d'));
        $sheet->setCellValue('N'.$row,$tba?null:$date?->format('M'));
        $sheet->setCellValue('P'.$row,$tba?null:$date?->format('Y'));
    }

    private function normalizeForA4(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().$sheet->getHighestRow())->getAlignment()->setWrapText(false);
        $lastColumn=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for($index=1;$index<=$lastColumn;$index++){$column=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);$dimension=$sheet->getColumnDimension($column);$width=$dimension->getWidth();if($width>0)$dimension->setWidth($width*self::COLUMN_SCALE);}
        foreach($sheet->getCoordinates(false) as $coordinate){$font=$sheet->getStyle($coordinate)->getFont();$font->setSize(max(5,$font->getSize()*self::FONT_SCALE));}
        foreach($sheet->getDrawingCollection() as $drawing){
            $drawing->setWidth((int)round($drawing->getWidth()*self::DRAWING_SCALE));
            $drawing->setHeight((int)round($drawing->getHeight()*self::DRAWING_SCALE));
            if(stripos($drawing->getName(),'LOGO')!==false)$this->centerLogoInHeader($sheet,$drawing);
            if(stripos($drawing->getName(),'TTD')===0)$this->centerSignatureInApprovalBox($sheet,$drawing);
        }
        $sheet->getPageMargins()->setTop(0.15)->setRight(0.15)->setBottom(0.15)->setLeft(0.15);
        $sheet->getPageSetup()->setOrientation('portrait')->setPaperSize(9)->setFitToWidth(1)->setFitToHeight(1);
    }
}
