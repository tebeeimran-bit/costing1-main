<?php

namespace App\Services\TrackingDocument;

use App\Models\DocumentRevision;
use App\Models\CostingData;
use App\Models\Material;
use App\Models\MaterialBreakdown;
use App\Models\UnpricedPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TrackingDocumentUnpricedPartService
{
    public function submitImportedPrices(DocumentRevision $revision, int $actorUserId): array
    {
        $costingEditPath = trim((string) $revision->costing_edit_file_path);
        $hasCostingEditWorkbook = $costingEditPath !== ''
            && Storage::disk('local')->exists($costingEditPath);

        $rows = UnpricedPart::where('document_revision_id', $revision->id)
            ->whereNull('resolved_at')
            ->whereNotNull('new_part_price_imported_at')
            ->where('detected_price', '>', 0)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            throw new \RuntimeException('Belum ada harga hasil Import New Part Request yang siap di-submit.');
        }

        $submitted = 0;
        $errors = [];
        foreach ($rows as $row) {
            try {
                $this->updatePrice($revision, [
                    'part_number' => $row->part_number,
                    'manual_price' => (float) $row->detected_price,
                    'purchase_unit' => (string) ($row->purchase_unit ?? ''),
                    'currency' => (string) ($row->currency ?? ''),
                    'moq' => $row->moq,
                    'cn_type' => (string) ($row->cn_type ?? ''),
                    'maker' => (string) ($row->maker ?? ''),
                    'add_cost_percent' => $row->add_cost_percent,
                    'use_database_lookup' => false,
                    // The Form Costing database is the primary target. The
                    // imported costing workbook is synchronized only when the
                    // project actually has that file stored.
                    'update_costing_edit' => $hasCostingEditWorkbook,
                    '_actor_user_id' => $actorUserId,
                ]);
                $submitted++;
            } catch (\Throwable $exception) {
                $errors[] = $row->part_number . ': ' . $exception->getMessage();
            }
        }

        if ($submitted === 0) {
            throw new \RuntimeException('Submit gagal. ' . implode(' | ', array_slice($errors, 0, 3)));
        }

        $message = "{$submitted} harga part berhasil diterapkan ke Material dan Form Costing.";
        if ($hasCostingEditWorkbook) {
            $message .= ' File Import Hasil Edit juga telah diperbarui.';
        }
        if ($errors !== []) {
            $message .= ' Sebagian belum diproses: ' . implode(' | ', array_slice($errors, 0, 3));
        }

        return ['submitted' => $submitted, 'errors' => $errors, 'message' => $message];
    }

    public function updatePrice(DocumentRevision $revision, array $validated): array
    {
        $partNumber = trim((string) $validated['part_number']);
        $partKey = strtolower($partNumber);
        $manualPrice = floatval($validated['manual_price'] ?? 0);
        $useDatabaseLookup = (bool) ($validated['use_database_lookup'] ?? false);
        $actorUserId = isset($validated['_actor_user_id']) ? (int) $validated['_actor_user_id'] : null;
        $state = [
            'ok' => true,
            'applied_price' => $manualPrice,
            'applied_currency' => strtoupper(trim((string) ($validated['currency'] ?? ''))),
            'applied_purchase_unit' => trim((string) ($validated['purchase_unit'] ?? '')),
            'applied_moq' => array_key_exists('moq', $validated) ? $validated['moq'] : null,
            'applied_cn' => strtoupper(trim((string) ($validated['cn_type'] ?? ''))),
            'applied_maker' => trim((string) ($validated['maker'] ?? '')),
            'applied_add_cost_import_tax' => array_key_exists('add_cost_percent', $validated) ? $validated['add_cost_percent'] : null,
            'resolution_source' => 'realtime_manual_input',
        ];

        $backupPath = null;
        if (!empty($validated['update_costing_edit'])) {
            $backupPath = $this->updateCostingEditWorkbook($revision, $partNumber, $state);
            $state['resolution_source'] = 'new_part_request_import';
        }

        try {
        DB::transaction(function () use ($revision, $partKey, $partNumber, $useDatabaseLookup, $actorUserId, &$state) {
            $openRows = UnpricedPart::where('document_revision_id', $revision->id)
                ->whereNull('resolved_at')
                ->whereRaw('lower(part_number) = ?', [$partKey])
                ->get();

            $partName = trim((string) ($openRows->first()?->part_name ?? ''));
            if ($state['applied_price'] <= 0 && $useDatabaseLookup) {
                $matchedMaterial = $this->findMaterialForUnpricedPart($partNumber, $partName);
                if ($matchedMaterial) {
                    $state['applied_price'] = floatval($matchedMaterial->price ?? 0);
                    $state['applied_currency'] = trim((string) ($matchedMaterial->currency ?? ''));
                    $state['applied_purchase_unit'] = trim((string) ($matchedMaterial->purchase_unit ?? ''));
                    $state['applied_moq'] = $matchedMaterial->moq;
                    $state['applied_cn'] = trim((string) ($matchedMaterial->cn ?? ''));
                    $state['applied_maker'] = trim((string) ($matchedMaterial->maker ?? ''));
                    $state['applied_add_cost_import_tax'] = $matchedMaterial->add_cost_import_tax;
                    $state['resolution_source'] = 'realtime_db_lookup';
                }
            }

            if ($state['applied_price'] > 0) {
                $material = Material::firstOrCreate(
                    ['material_code' => $partNumber],
                    [
                        'material_description' => $partName ?: null,
                        'base_uom' => 'PCS',
                        'currency' => $state['applied_currency'] !== '' ? $state['applied_currency'] : 'IDR',
                        'price' => 0,
                    ]
                );
                $material->price = $state['applied_price'];
                if ($state['applied_currency'] !== '') {
                    $material->currency = $state['applied_currency'];
                }
                if ($state['applied_purchase_unit'] !== '') {
                    $material->purchase_unit = $state['applied_purchase_unit'];
                }
                if ($state['applied_moq'] !== null) $material->moq = $state['applied_moq'];
                if ($state['applied_cn'] !== '') $material->cn = $state['applied_cn'];
                if ($state['applied_maker'] !== '') $material->maker = $state['applied_maker'];
                if ($state['applied_add_cost_import_tax'] !== null) $material->add_cost_import_tax = $state['applied_add_cost_import_tax'];
                $material->price_update = now()->toDateString();
                $material->save();

                $costingDataId = CostingData::where('tracking_revision_id', $revision->id)->latest('id')->value('id');
                if ($costingDataId) {
                    MaterialBreakdown::where('costing_data_id', $costingDataId)
                        ->whereRaw('lower(part_no) = ?', [$partKey])
                        ->update([
                            'amount1' => $state['applied_price'],
                            'unit_price_basis' => null,
                            'unit_price_basis_text' => $state['applied_purchase_unit'] !== '' ? $state['applied_purchase_unit'] : null,
                            'currency' => $state['applied_currency'] !== '' ? $state['applied_currency'] : null,
                            'qty_moq' => $state['applied_moq'],
                            'cn_type' => $state['applied_cn'] !== '' ? $state['applied_cn'] : null,
                            'supplier' => $state['applied_maker'] !== '' ? $state['applied_maker'] : null,
                            'import_tax_percent' => $state['applied_add_cost_import_tax'],
                            'updated_at' => now(),
                        ]);
                    $this->recalculateCostingMaterialTotal((int) $costingDataId);
                }

                foreach ($openRows as $row) {
                    $row->update([
                        'manual_price' => $state['applied_price'],
                        'resolved_at' => now(),
                        'resolved_by_id' => $actorUserId,
                        'resolution_source' => $state['resolution_source'],
                    ]);
                }
            } else {
                foreach ($openRows as $row) {
                    $row->update(['manual_price' => null]);
                }
            }

            $this->syncRevisionStatus($revision);
        });
        } catch (\Throwable $exception) {
            if ($backupPath && is_file($backupPath) && $revision->costing_edit_file_path) {
                @copy($backupPath, Storage::disk('local')->path($revision->costing_edit_file_path));
            }
            throw $exception;
        } finally {
            if ($backupPath && is_file($backupPath)) @unlink($backupPath);
        }

        return $this->buildStatusPayload($revision, $state);
    }

    private function updateCostingEditWorkbook(DocumentRevision $revision, string $partNumber, array $state): string
    {
        @set_time_limit(180);
        @ini_set('memory_limit', '1536M');
        $storedPath = trim((string) $revision->costing_edit_file_path);
        if ($storedPath === '' || !Storage::disk('local')->exists($storedPath)) {
            throw new \RuntimeException('File Import Hasil Edit belum tersedia.');
        }

        $filePath = Storage::disk('local')->path($storedPath);
        $backupPath = tempnam(sys_get_temp_dir(), 'costing-edit-backup-');
        if (!$backupPath || !copy($filePath, $backupPath)) {
            throw new \RuntimeException('Gagal membuat cadangan file Import Hasil Edit.');
        }

        try {
            $workbook = IOFactory::load($filePath);
            $sheet = $workbook->getSheetByName('Material Cost');
            if (!$sheet) throw new \RuntimeException('Sheet Material Cost tidak ditemukan.');

            $updated = 0;
            for ($row = 18; $row <= $sheet->getHighestDataRow(); $row++) {
                $sourcePartNumber = trim((string) $sheet->getCell("D{$row}")->getFormattedValue());
                if (strcasecmp($sourcePartNumber, $partNumber) !== 0) continue;

                $hasExistingValue = false;
                foreach (range('L', 'R') as $column) {
                    if (trim((string) $sheet->getCell("{$column}{$row}")->getFormattedValue()) !== '') {
                        $hasExistingValue = true;
                        break;
                    }
                }
                if ($hasExistingValue) continue;

                $sheet->setCellValue("L{$row}", $state['applied_price']);
                $sheet->setCellValue("M{$row}", $state['applied_purchase_unit']);
                $sheet->setCellValue("N{$row}", $state['applied_currency']);
                $sheet->setCellValue("O{$row}", $state['applied_moq']);
                $sheet->setCellValue("P{$row}", $state['applied_cn']);
                $sheet->setCellValue("Q{$row}", $state['applied_maker']);
                $sheet->setCellValue("R{$row}", $state['applied_add_cost_import_tax']);
                $updated++;
            }
            if ($updated === 0) {
                throw new \RuntimeException('Part No tidak ditemukan atau kolom L-R pada file hasil edit sudah terisi.');
            }

            $writer = new Xlsx($workbook);
            $writer->setPreCalculateFormulas(false);
            $writer->save($filePath);
            $workbook->disconnectWorksheets();

            return $backupPath;
        } catch (\Throwable $exception) {
            @copy($backupPath, $filePath);
            @unlink($backupPath);
            throw $exception;
        }
    }

    private function recalculateCostingMaterialTotal(int $costingDataId): void
    {
        $costing = CostingData::find($costingDataId);
        if (!$costing) return;

        $forecast = (float) ($costing->forecast ?? 0);
        $projectLife = (float) ($costing->project_period ?? 0);
        $rates = [
            'IDR' => 1.0,
            'USD' => (float) ($costing->exchange_rate_usd ?? 0),
            'JPY' => (float) ($costing->exchange_rate_jpy ?? 0),
        ];
        $total = 0.0;

        foreach (MaterialBreakdown::where('costing_data_id', $costingDataId)->get() as $row) {
            $qty = (float) ($row->qty_req ?? 0);
            $price = (float) ($row->amount1 ?? 0);
            $moq = (float) ($row->qty_moq ?? 0);
            $tax = (float) ($row->import_tax_percent ?? 0);
            $unit = strtoupper(trim((string) ($row->unit ?? '')));
            $basis = strtoupper(trim((string) ($row->unit_price_basis_text ?? $row->unit_price_basis ?? '')));
            $cn = strtoupper(trim((string) ($row->cn_type ?? '')));

            if ($qty <= 0) {
                $factor = 0.0;
            } else {
                $denominator = $forecast * $projectLife * 12 * $qty;
                if ($unit === 'MM') $denominator /= 1000;
                $ratio = $denominator != 0.0 ? $moq / $denominator : 0.0;
                $factor = ($cn === 'C' || $ratio < 1) ? 1.0 : $ratio;
            }

            $basisDivisor = in_array($basis, ['METER', 'M', 'MTR'], true) ? 1000.0 : 1.0;
            $amount2 = ($factor * ($price + ($price * $tax / 100))) / $basisDivisor;
            $currency = strtoupper(trim((string) ($row->currency ?? 'IDR')));
            $rate = $rates[$currency] ?? 1.0;
            $total += $qty * $amount2 * $rate;

            $row->newQuery()->whereKey($row->id)->update([
                'amount2' => round($amount2, 6),
                'currency2' => $currency !== '' ? $currency : 'IDR',
            ]);
        }

        $costing->update(['material_cost' => round($total, 2)]);
    }

    public function delete(DocumentRevision $revision, string $partNumber): array
    {
        $partKey = strtolower(trim($partNumber));
        DB::transaction(function () use ($revision, $partKey) {
            UnpricedPart::where('document_revision_id', $revision->id)
                ->whereNull('resolved_at')
                ->whereRaw('lower(part_number) = ?', [$partKey])
                ->update(['resolved_at' => now(), 'resolution_source' => 'manual_delete']);
            $this->syncRevisionStatus($revision);
        });

        return $this->buildStatusPayload($revision, ['ok' => true]);
    }

    public function bulkDelete(DocumentRevision $revision, array $partNumbers): array
    {
        $partKeys = array_values(array_unique(array_map(fn ($p) => strtolower(trim((string) $p)), $partNumbers)));
        DB::transaction(function () use ($revision, $partKeys) {
            UnpricedPart::where('document_revision_id', $revision->id)
                ->whereNull('resolved_at')
                ->where(function ($q) use ($partKeys) {
                    foreach ($partKeys as $key) {
                        $q->orWhereRaw('lower(part_number) = ?', [$key]);
                    }
                })
                ->update(['resolved_at' => now(), 'resolution_source' => 'manual_delete']);
            $this->syncRevisionStatus($revision);
        });

        return $this->buildStatusPayload($revision, [
            'ok' => true,
            'deleted_count' => count($partKeys),
        ]);
    }

    public function restore(DocumentRevision $revision, string $partNumber): array
    {
        $partKey = strtolower(trim($partNumber));
        $restored = false;
        DB::transaction(function () use ($revision, $partKey, &$restored) {
            $target = UnpricedPart::where('document_revision_id', $revision->id)
                ->whereNotNull('resolved_at')
                ->whereRaw('lower(part_number) = ?', [$partKey])
                ->orderByDesc('resolved_at')
                ->orderByDesc('id')
                ->first();
            if ($target) {
                $target->update([
                    'manual_price' => null,
                    'resolved_at' => null,
                    'resolution_source' => 'undo_tambah',
                ]);
                $restored = true;
            }
            $this->syncRevisionStatus($revision);
        });

        return $this->buildStatusPayload($revision, ['ok' => true, 'restored' => $restored]);
    }

    private function buildStatusPayload(DocumentRevision $revision, array $payload): array
    {
        $fresh = $revision->fresh();
        return array_merge($payload, [
            'open_unpriced_count' => UnpricedPart::where('document_revision_id', $revision->id)->whereNull('resolved_at')->count(),
            'status' => $fresh->status,
            'status_label' => $fresh->status_label,
        ]);
    }

    private function syncRevisionStatus(DocumentRevision $revision): void
    {
        $hasOpenUnpriced = UnpricedPart::where('document_revision_id', $revision->id)
            ->whereNull('resolved_at')
            ->exists();

        if ($hasOpenUnpriced) {
            if ($revision->status !== DocumentRevision::STATUS_SUBMITTED_TO_MARKETING) {
                $revision->update(['status' => DocumentRevision::STATUS_PENDING_PRICING]);
            }
        } elseif ($revision->status === DocumentRevision::STATUS_PENDING_PRICING) {
            $revision->update([
                'status' => DocumentRevision::STATUS_COGM_GENERATED,
                'cogm_generated_at' => now(),
            ]);
        }
    }

    private function findMaterialForUnpricedPart(string $partNumber, string $partName = ''): ?Material
    {
        $normalizedPartNumber = trim($partNumber);
        $normalizedPartName = trim($partName);

        if ($normalizedPartNumber !== '') {
            $directByCode = Material::query()
                ->whereRaw('lower(material_code) = ?', [Str::lower($normalizedPartNumber)])
                ->where('price', '>', 0)
                ->orderByRaw('CASE WHEN price_update IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('price_update')
                ->orderByDesc('id')
                ->first();
            if ($directByCode) {
                return $directByCode;
            }

            $escapedPartNumber = $this->escapeLikeKeyword($normalizedPartNumber);
            $byDescriptionFromPartNumber = Material::query()
                ->where('price', '>', 0)
                ->where(function ($query) use ($normalizedPartNumber, $escapedPartNumber) {
                    $query->whereRaw('lower(material_description) = ?', [Str::lower($normalizedPartNumber)])
                        ->orWhereRaw('lower(material_description) like ?', ['%' . Str::lower($escapedPartNumber) . '%']);
                })
                ->orderByRaw('CASE WHEN price_update IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('price_update')
                ->orderByDesc('id')
                ->first();
            if ($byDescriptionFromPartNumber) {
                return $byDescriptionFromPartNumber;
            }
        }

        if ($normalizedPartName !== '') {
            $escapedPartName = $this->escapeLikeKeyword($normalizedPartName);
            $byDescriptionFromPartName = Material::query()
                ->where('price', '>', 0)
                ->where(function ($query) use ($normalizedPartName, $escapedPartName) {
                    $query->whereRaw('lower(material_description) = ?', [Str::lower($normalizedPartName)])
                        ->orWhereRaw('lower(material_description) like ?', ['%' . Str::lower($escapedPartName) . '%']);
                })
                ->orderByRaw('CASE WHEN price_update IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('price_update')
                ->orderByDesc('id')
                ->first();
            if ($byDescriptionFromPartName) {
                return $byDescriptionFromPartName;
            }
        }

        $normalizedPartNumberKey = $this->normalizeLookupKey($normalizedPartNumber);
        $normalizedPartNameKey = $this->normalizeLookupKey($normalizedPartName);
        if ($normalizedPartNumberKey === '' && $normalizedPartNameKey === '') {
            return null;
        }

        $searchSource = trim($normalizedPartNumber . ' ' . $normalizedPartName);
        $tokenCandidates = collect(preg_split('/[^a-z0-9]+/i', Str::lower($searchSource)) ?: [])
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => strlen($token) >= 3)
            ->unique()
            ->values();

        $candidateQuery = Material::query()
            ->where('price', '>', 0)
            ->where(function ($query) {
                $query->whereNotNull('material_code')->orWhereNotNull('material_description');
            });

        if ($tokenCandidates->isNotEmpty()) {
            $candidateQuery->where(function ($query) use ($tokenCandidates) {
                foreach ($tokenCandidates as $token) {
                    $escapedToken = $this->escapeLikeKeyword((string) $token);
                    $query->orWhereRaw('lower(material_code) like ?', ['%' . $escapedToken . '%'])
                        ->orWhereRaw('lower(material_description) like ?', ['%' . $escapedToken . '%']);
                }
            });
        }

        $candidates = $candidateQuery
            ->orderByRaw('CASE WHEN price_update IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('price_update')
            ->orderByDesc('id')
            ->limit(3000)
            ->get();

        foreach ($candidates as $candidate) {
            $candidateCodeKey = $this->normalizeLookupKey((string) ($candidate->material_code ?? ''));
            $candidateDescriptionKey = $this->normalizeLookupKey((string) ($candidate->material_description ?? ''));
            if ($this->isNormalizedLookupMatch($normalizedPartNumberKey, $candidateCodeKey)
                || $this->isNormalizedLookupMatch($normalizedPartNumberKey, $candidateDescriptionKey)
                || $this->isNormalizedLookupMatch($normalizedPartNameKey, $candidateDescriptionKey)) {
                return $candidate;
            }
        }

        return null;
    }

    private function escapeLikeKeyword(string $keyword): string
    {
        return addcslashes($keyword, '\\%_');
    }

    private function normalizeLookupKey(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(trim($value))) ?? '';
    }

    private function isNormalizedLookupMatch(string $sourceKey, string $targetKey): bool
    {
        if ($sourceKey === '' || $targetKey === '') {
            return false;
        }
        if ($sourceKey === $targetKey) {
            return true;
        }
        return str_contains($sourceKey, $targetKey) || str_contains($targetKey, $sourceKey);
    }
}
