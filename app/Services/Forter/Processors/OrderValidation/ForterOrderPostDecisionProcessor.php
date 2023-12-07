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

class ForterOrderPostDecisionProcessor extends AbstractForterOrderDecisionProcessor
{
    /**
     * 'pre' / 'post'
     * @var string
     */
    public const AUTH_STEP = 'post';

    /**
     * @method canProcessOrder
     * @param  ForterOrder     $order
     * @return bool
     */
    public function canProcessOrder(ForterOrder $order)
    {
        if ($this->canProcessOrder === null) {
            // Return false if order doesn't have a payment (and a transaction if required).
            if (!$order->hasPayments() || (UtilsHelper::isForterPostOrderValidationRequirePaymentTransaction() && !$order->hasPaymentTransactions())) {
                Log::info("[ForterOrderPostDecisionProcessor::getOrderPostDecision] [SKIPPING] This order has no payment (or required transactions) | Order ID:{$order->getForterOrderId()}} | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()}");
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
            Log::warning("[ForterOrderPostDecisionProcessor::process] [SKIPPING] " . UtilsHelper::APP_IS_DISABLED_MSG . " | Order ID:{$order->getForterOrderId()}} | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()}");
            return false;
        }

        // Return false if post auth is disabled by config.
        if (!UtilsHelper::isForterPostOrderValidationEnabled()) {
            Log::warning("[ForterOrderPostDecisionProcessor::process] [SKIPPING] Forter post auth is currently disabled.
                Skipping order validation and further processing at this point.
                If this is intended, it's recommended to disable the subscription for this message type.
                *Fix automatically by goint through the app installation again.");
            return false;
        }

        $order = $this->getOrderModel();

        Log::debug("[ForterOrderPostDecisionProcessor::process] [START] | Order ID:{$order->getForterOrderId()} | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()}");

        // Get Forter post decision.
        $this->forterResponse = $this->getOrderPostDecision($order);

        // Return false if response is not ForterResponse.
        if ($this->forterResponse === false) {
            return false;
        }

        // Build Commercetools actions based on Forter decision/recommendations and app configuration (set custom fields and/or set order state).
        $decisionActions = $this->getOrderDecisionActions($order, $this->forterResponse);

        if ($decisionActions) {
            // Pull fresh order with current version
            $order = CommercetoolsOrdersService::getById($order->getId());

            // Update Order
            $order = CommercetoolsOrdersService::updateById($order->getId(), $this->getPreparedCommercetoolsUpdateActions(), $order->getVersion());

            $order = ForterOrder::getInstance($order);

            $this->setOrderModel($order);
        }

        Log::debug("[ForterOrderPostDecisionProcessor::process] [END] | Order ID:{$order->getForterOrderId()} | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()}");

        return $this;
    }

    /**
     * @method getOrderPostDecision
     * @param  ForterOrder                     $order
     * @return ForterResponse|false
     */
    public function getOrderPostDecision(ForterOrder $order)
    {
        // Return false if app is disabled or post auth is disabled by config.
        if (
            !UtilsHelper::isForterEnabled() ||
            !UtilsHelper::isForterPostOrderValidationEnabled()
        ) {
            return false;
        }

        // If enabled on config (for testing only!) - mock missing order/payment/transaction data before processing
        $order = UtilsHelper::maybeMockMissingDataIfEnabledForTesting($order, true);

        // Return false if order doesn't have a payment (and a transaction if required).
        if (!$this->canProcessOrder($order)) {
            return false;
        }

        // Send to v2/orders - Get Forter decision.
        $this->forterResponse = ForterApiService::makeOrderValidationRequest($order, $this->getAuthStep());
        Log::debug("{$this->getAuthStep()} auth response - {$this->getMessageType()} - v2/orders/{$order->getForterOrderId()}", ['resourceId' => $this->getResourceId(), 'messageType' => $this->getMessageType(), 'messageId' => $this->getMessageId(), 'messagePayload' => $this->getMessageModel()->getMessageData(), 'forterOrderValidationResponse' => $this->forterResponse->getResponseData()]);

        // Send to v2/status
        $forterOrderStatusResponse = ForterApiService::makeOrderStatusRequest($order);

        return $this->forterResponse;
    }
}
