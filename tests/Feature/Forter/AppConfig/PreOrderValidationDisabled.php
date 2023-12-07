<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace Tests\Feature\Forter\AppConfig;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Feature\Forter\AbstractForterTest;
use App\Helpers\Forter\ForterMockGenerator;
use App\Helpers\UtilsHelper;

class PreOrderValidationDisabled extends AbstractForterTest
{
    public const CONFIG = [
        'forter.is_enabled' => true,
        'forter.pre_order_validation_enabled' => false,
        'forter.decision_actions.approve.post' => 'DO_NOTHING',
    ];

    /**
     * @method test_forter_app_config_forter_pre_order_validation_disabled
     */
    public function test_forter_app_config_forter_pre_order_validation_disabled(): void
    {
        $orderMock = ForterMockGenerator::getDecisionReadyCreditCardOrderMock('approve', false, false);
        $payload = ForterMockGenerator::wrapOrderMockAsApiExtension($orderMock);
        $payload = $this->addTestConfigToPayload($payload);
        $response = $this
            ->withHeaders($this->getApiExtensionsRequestHeaders())
            ->postJson('/commercetools/api/extensions', $payload);

        $response->assertNoContent(200);
    }
}
