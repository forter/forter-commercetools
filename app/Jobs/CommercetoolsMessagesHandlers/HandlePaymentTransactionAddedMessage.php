<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Jobs\CommercetoolsMessagesHandlers;

use App\Services\Commercetools\CommercetoolsOrdersService;
use App\Services\Forter\Processors\OrderValidation\ForterOrderPostDecisionProcessor;

class HandlePaymentTransactionAddedMessage extends AbstractMessageHandler
{
    /**
     * Handle Message
     * @return void
     */
    protected function handleMessage()
    {
        // Get order by payment ID
        $order = CommercetoolsOrdersService::getOrderByPaymentId($this->getResourceId(), ['paymentInfo.payments[*]', 'cart']);
        $this->setOrderModel($order);

        // Handle post auth on PaymentTransactionAdded
        $processor = new ForterOrderPostDecisionProcessor($this->getMessageModel());
        return $processor->process();
    }
}
