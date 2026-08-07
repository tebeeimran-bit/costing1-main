<?php

namespace App\Console\Commands;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\Pic;
use App\Models\Plant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ImportMasterData extends Command
{
    protected $signature = 'app:import-master-data {file : Lokasi file Excel master data}';

    protected $description = 'Mengimpor Customer, Business Categories, Plant, dan PIC dari sheet Excel';

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('file'));
        if (! is_file($path)) {
            $this->components->error('File tidak ditemukan: '.$path);

            return self::FAILURE;
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $workbook = $reader->load($path);

            $sheets = [];
            foreach ($workbook->getWorksheetIterator() as $sheet) {
                $sheets[mb_strtoupper(trim($sheet->getTitle()))] = $sheet;
            }

            foreach (['CUSTOMER', 'BUSINESS CATEGORIES', 'PLANT', 'PIC'] as $requiredSheet) {
                if (! isset($sheets[$requiredSheet])) {
                    throw new RuntimeException("Sheet {$requiredSheet} tidak ditemukan.");
                }
            }

            $result = DB::transaction(fn () => [
                'customer' => $this->importCustomers($sheets['CUSTOMER']),
                'category' => $this->importCodeAndName($sheets['BUSINESS CATEGORIES'], BusinessCategory::class),
                'plant' => $this->importCodeAndName($sheets['PLANT'], Plant::class),
                'pic' => $this->importPics($sheets['PIC']),
            ]);
        } catch (\Throwable $exception) {
            $this->components->error('Import dibatalkan: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Import master data berhasil.');
        $this->table(['Data', 'Diproses', 'Dilewati'], [
            ['Customer', $result['customer']['processed'], $result['customer']['skipped']],
            ['Business Category', $result['category']['processed'], $result['category']['skipped']],
            ['Plant', $result['plant']['processed'], $result['plant']['skipped']],
            ['PIC Engineering', $result['pic']['engineering'], 0],
            ['PIC Marketing', $result['pic']['marketing'], $result['pic']['blank_marketing']],
        ]);

        return self::SUCCESS;
    }

    private function importCustomers(Worksheet $sheet): array
    {
        $processed = 0;
        $skipped = 0;

        foreach ($this->rows($sheet) as $row) {
            $code = mb_strtoupper($this->normalizeText($row['b']));
            $name = $this->normalizeCustomerName($row['c']);

            if ($code === '' || $name === '') {
                $skipped++;
                continue;
            }

            if ($code === 'KMII' && mb_strtolower($name) === 'kramat motor, pt') {
                $skipped++;
                continue;
            }

            Customer::updateOrCreate(['code' => $code], ['name' => $name]);
            $processed++;
        }

        return compact('processed', 'skipped');
    }

    private function importCodeAndName(Worksheet $sheet, string $model): array
    {
        $processed = 0;
        $skipped = 0;

        foreach ($this->rows($sheet) as $row) {
            $code = mb_strtoupper($this->normalizeText($row['b']));
            $name = $this->normalizeText($row['c']);
            if ($code === '' || $name === '') {
                $skipped++;
                continue;
            }

            $model::updateOrCreate(['code' => $code], ['name' => $name]);
            $processed++;
        }

        return compact('processed', 'skipped');
    }

    private function importPics(Worksheet $sheet): array
    {
        $engineering = 0;
        $marketing = 0;
        $blankMarketing = 0;

        foreach ($this->rows($sheet) as $row) {
            $engineeringName = $this->normalizeText($row['b']);
            $marketingName = $this->normalizeText($row['c']);

            if ($engineeringName !== '') {
                Pic::firstOrCreate(['name' => $engineeringName, 'type' => 'engineering']);
                $engineering++;
            }
            if ($marketingName !== '') {
                Pic::firstOrCreate(['name' => $marketingName, 'type' => 'marketing']);
                $marketing++;
            } else {
                $blankMarketing++;
            }
        }

        return ['engineering' => $engineering, 'marketing' => $marketing, 'blank_marketing' => $blankMarketing];
    }

    private function rows(Worksheet $sheet): iterable
    {
        for ($row = 3; $row <= $sheet->getHighestDataRow(); $row++) {
            yield [
                'b' => (string) $sheet->getCell('B'.$row)->getValue(),
                'c' => (string) $sheet->getCell('C'.$row)->getValue(),
            ];
        }
    }

    private function normalizeText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function normalizeCustomerName(string $value): string
    {
        $value = $this->normalizeText($value);

        return trim((string) preg_replace('/\s*,\s*/u', ', ', $value));
    }

    private function resolvePath(string $path): string
    {
        $isWindowsAbsolute = strlen($path) >= 3
            && ctype_alpha($path[0])
            && $path[1] === ':'
            && in_array($path[2], ['\\', '/'], true);
        $isUncPath = str_starts_with($path, '\\\\') || str_starts_with($path, '//');

        if ($isWindowsAbsolute || $isUncPath) {
            return $path;
        }

        return base_path($path);
    }
}
