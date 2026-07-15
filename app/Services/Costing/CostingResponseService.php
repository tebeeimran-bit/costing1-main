<?php

namespace App\Services\Costing;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CostingResponseService
{
    public function buildRedirectUrl(int $costingDataId, ?int $trackingRevisionId): string
    {
        $redirectParams = ['id' => $costingDataId];
        if ($trackingRevisionId) {
            $redirectParams['tracking_revision_id'] = $trackingRevisionId;
        }

        return route('form', $redirectParams, false);
    }

    public function buildSuccessMessage(string $updateSection, bool $importFromPartlist, int $partlistCount, bool $importFromCycleTime, int $cycleTimeCount): string
    {
        $sectionLabels = [
            'informasi_project' => 'Informasi Project',
            'rates' => 'Rates',
            'material' => 'Material',
            'unpriced_parts' => 'Rekapan Part Tanpa Harga',
            'cycle_time' => 'Cycle Time',
            'resume_cogm' => 'Resume COGM',
        ];

        $successMessage = $updateSection !== ''
            ? (($sectionLabels[$updateSection] ?? 'Section') . ' berhasil diupdate!')
            : 'Data costing berhasil disimpan!';

        if ($importFromPartlist) {
            return 'Partlist berhasil diimport ke Material (' . $partlistCount . ' baris).';
        }

        if ($importFromCycleTime) {
            return 'Cycle Time berhasil diimport (' . $cycleTimeCount . ' baris).';
        }

        return $successMessage;
    }


    public function resolveRedirectTarget(mixed $response): ?string
    {
        if ($response instanceof RedirectResponse) {
            return $response->getTargetUrl();
        }

        if ($response instanceof Response && $response->getStatusCode() === 302) {
            return $response->headers->get('Location');
        }

        if (is_object($response) && method_exists($response, 'getStatusCode') && $response->getStatusCode() === 302) {
            return $response->headers->get('Location');
        }

        return null;
    }

    public function buildThinRedirectPage(string $redirectUrl): Response
    {
        return response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' .
            '<script>window.location.replace(' . json_encode($redirectUrl) . ');</script>' .
            '</body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    public function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }
}
