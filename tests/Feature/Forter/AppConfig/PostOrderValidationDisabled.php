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

class PostOrderValidationDisabled extends AbstractForterTest
{
    public const CONFIG = [
        'forter.is_enabled' => true,
        'forter.post_order_validation_enabled' => false,
        'forter.decision_actions.approve.post' => 'DO_NOTHING',
    ];

    /**
     * @method test_forter_app_config_forter_post_order_validation_disabled
     */
    public function test_forter_app_config_forter_post_order_validation_disabled(): void
    {
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
