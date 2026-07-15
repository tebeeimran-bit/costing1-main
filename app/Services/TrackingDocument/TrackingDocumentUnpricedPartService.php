<?php

namespace App\Services\TrackingDocument;

use App\Models\DocumentRevision;
use App\Models\Material;
use App\Models\UnpricedPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrackingDocumentUnpricedPartService
{
    public function updatePrice(DocumentRevision $revision, array $validated): array
    {
        $partNumber = trim((string) $validated['part_number']);
        $partKey = strtolower($partNumber);
        $manualPrice = floatval($validated['manual_price'] ?? 0);
        $useDatabaseLookup = (bool) ($validated['use_database_lookup'] ?? false);
        $state = [
            'applied_price' => $manualPrice,
            'applied_currency' => '',
            'applied_purchase_unit' => '',
            'applied_moq' => null,
            'applied_cn' => '',
            'applied_maker' => '',
            'applied_add_cost_import_tax' => null,
            'resolution_source' => 'realtime_manual_input',
        ];

        DB::transaction(function () use ($revision, $partKey, $partNumber, $useDatabaseLookup, &$state) {
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
                $material->price_update = now()->toDateString();
                $material->save();

                foreach ($openRows as $row) {
                    $row->update([
                        'manual_price' => $state['applied_price'],
                        'resolved_at' => now(),
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

        return $this->buildStatusPayload($revision, $state);
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
