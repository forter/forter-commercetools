<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Services\Forter\Processors\OrderValidation;

use Illuminate\Support\Facades\Log;
use App\Services\Commercetools\CommercetoolsOrdersService;
use App\Models\Forter\ForterCommercetoolsMessage;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterResponse;
use App\Services\Forter\ForterApiService;
use App\Services\Forter\SchemaBuilders\ForterDecisionActionsBuilder;
use App\Helpers\UtilsHelper;
use App\Services\Forter\Processors\OrderValidation\AbstractForterOrderDecisionProcessor;

class ForterOrderPreDecisionProcessor extends AbstractForterOrderDecisionProcessor
{
    /**
     * 'pre' / 'post'
     * @var string
     */
    public const AUTH_STEP = 'pre';

    /**
     * @method canProcessOrder
     * @param  ForterOrder     $order
     * @return bool
     */
    public function canProcessOrder(ForterOrder $order)
    {
        if ($this->canProcessOrder === null) {
            // Return false if order doesn't have a payment.
            if (!$order->hasPayments()) {
                Log::info("[ForterOrderPostDecisionProcessor::getOrderPostDecision] [SKIPPING] This order has no payment | Order ID:{$order->getForterOrderId()}} | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()}");
                $this->canProcessOrder = false;
            }
            $this->canProcessOrder = true;
        }

        return $this->canProcessOrder;
    }

    /**
     * @method process
     * @return ForterOrderPostDecisionProcessor|false
     */
    public function process()
    {
        // Return false if app is disabled
        if (!UtilsHelper::isForterEnabled()) {
            Log::warning("[ForterOrderPreDecisionProcessor::process] [SKIPPING] " . UtilsHelper::APP_IS_DISABLED_MSG . " | Order ID:{$order->getForterOrderId()}} | Trigger:{$this->getMessageTrigger()}");
            return false;
        }

        // Return false if pre auth is disabled by config.
        if (!UtilsHelper::isForterPreOrderValidationEnabled()) {
            Log::warning("[ForterOrderPreDecisionProcessor::process] [SKIPPING] Forter pre auth is currently disabled. Skipping order validation at this point | Order ID:{$order->getForterOrderId()}} | Trigger:{$this->getMessageTrigger()}");
            return false;
        }

        $order = $this->getOrderModel();

        Log::debug("[ForterOrderPreDecisionProcessor::process] [START] | Order ID:{$order->getForterOrderId()} | Trigger:{$this->getMessageTrigger()}");

        // Get Forter pre decision.
        $forterResponse = $this->getOrderPreDecision($order);

        // Return false if response is not ForterResponse.
        if ($forterResponse === false) {
            return false;
        }

        // Build Commercetools actions based on Forter decision/recommendations and app configuration (set custom fields and/or set order state).
        $decisionActions = $this->getOrderDecisionActions($order, $forterResponse);

        return $this;
    }

    /**
     * @method getOrderPreDecision
     * @param  ForterOrder                     $order
     * @return ForterResponse
     */
    public function getOrderPreDecision(ForterOrder $order)
    {
        // Return false if app is disabled or pre auth is disabled by config.
        if (
            !UtilsHelper::isForterEnabled() ||
            !UtilsHelper::isForterPreOrderValidationEnabled()
        ) {
            return false;
        }

        // If enabled on config (for testing only!) - mock missing order/payment/transaction data before processing
        $order = UtilsHelper::maybeMockMissingDataIfEnabledForTesting($order, false);

        // Return false if order doesn't have a payment.
        if (!$this->canProcessOrder($order)) {
            return false;
        }

        // Send to v2/orders - Get Forter decision.
        $this->forterResponse = ForterApiService::makeOrderValidationRequest($order, $this->getAuthStep());
        Log::debug("{$this->getAuthStep()} auth response - v2/orders/{$order->getForterOrderId()}", ['trigger' => $this->getMessageTrigger(), 'requestBody' => $this->messageModel->getMessageData(), 'forterOrderValidationResponse' => $this->forterResponse->getResponseData()]);

        // Send to v2/status
        $forterOrderStatusResponse = ForterApiService::makeOrderStatusRequest($order);

        return $this->forterResponse;
    }
}
