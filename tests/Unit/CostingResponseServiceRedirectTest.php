<?php

namespace Tests\Unit;

use App\Services\Costing\CostingResponseService;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class CostingResponseServiceRedirectTest extends TestCase
{
    public function test_it_resolves_redirect_target_from_redirect_response(): void
    {
        $service = new CostingResponseService();
        $response = redirect('/form?id=123');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(url('/form?id=123'), $service->resolveRedirectTarget($response));
    }

    public function test_it_builds_thin_redirect_page(): void
    {
        $service = new CostingResponseService();
        $response = $service->buildThinRedirectPage('/target');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('window.location.replace', $response->getContent());
        $this->assertStringContainsString('/target', $response->getContent());
    }
}
