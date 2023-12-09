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
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Tests\Feature\Forter\AbstractForterTest;
use App\Helpers\Forter\ForterMockGenerator;
use App\Models\Forter\ForterCommercetoolsMessage;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterResponse;
use App\Helpers\UtilsHelper;
use App\Services\Forter\Processors\OrderValidation\ForterOrderPostDecisionProcessor;

class ForterIsDisabled extends AbstractForterTest
{
    public const CONFIG = [
        'forter.is_enabled' => false,
        'forter.pre_order_validation_enabled' => true,
        'forter.post_order_validation_enabled' => true,
        'forter.decision_actions.approve.post' => 'DO_NOTHING',
    ];

    /**
     * @method test_forter_app_config_forter_is_disabled
     */
    public function test_forter_app_config_forter_is_disabled(): void
    {
        // Check that API-extension endpoint returns 200 with no content when app is disabled
        $orderMock = ForterMockGenerator::getDecisionReadyCreditCardOrderMock('approve', false, false);
        $payload = ForterMockGenerator::wrapOrderMockAsApiExtension($orderMock);
        $payload = $this->addTestConfigToPayload($payload);
        $response = $this
            ->withHeaders($this->getApiExtensionsRequestHeaders())
            ->postJson('/commercetools/api/extensions', $payload);

        $response->assertNoContent(200);

        // Check that getOrderPostDecision returns false when app is disabled
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyCreditCardOrderMock('approve', true, true);
        $payload = ForterMockGenerator::wrapOrderMockAsSubscriptionMessage($orderMock);

        $messageModel = ForterCommercetoolsMessage::getInstance($payload);
        $order = $messageModel->getOrderModel();

        $this->assertInstanceOf(ForterCommercetoolsMessage::class, $messageModel);
        $this->assertInstanceOf(ForterOrder::class, $order);

        $processor = new ForterOrderPostDecisionProcessor($messageModel);

        $forterResponse = $processor->getOrderPostDecision($order);
        $this->assertFalse($forterResponse);
    }
}
