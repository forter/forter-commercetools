<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace Tests\Feature\Forter;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Feature\Forter\AbstractForterTest;

class CommercetoolsApiExtensionsGuard extends AbstractForterTest
{
    /**
     * @method test_commercetools_api_extension_route_guard_with_wrong_authorization
     */
    public function test_commercetools_api_extension_route_guard_with_wrong_authorization(): void
    {
        $response = $this
            ->withHeaders([
                'authorization' => 'Basic ' . \base64_encode('WRONG_SECRET'),
                'x-correlation-id' => 'forter-commercetools-app-test-guard-with-wrong-authorization',
            ])
            ->postJson('/commercetools/api/extensions', []);

        $response->assertStatus(401);
    }
}
