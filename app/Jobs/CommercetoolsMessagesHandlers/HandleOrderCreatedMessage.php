<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Jobs\CommercetoolsMessagesHandlers;

use App\Services\Forter\Processors\OrderValidation\ForterOrderPostDecisionProcessor;

class HandleOrderCreatedMessage extends AbstractMessageHandler
{
    /**
     * Handle Message
     * @return void
     */
    protected function handleMessage()
    {
        // Handle post auth on OrderCreated
        $processor = new ForterOrderPostDecisionProcessor($this->getMessageModel());
        return $processor->process();
    }
}
