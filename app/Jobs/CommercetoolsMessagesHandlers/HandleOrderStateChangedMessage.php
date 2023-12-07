<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Jobs\CommercetoolsMessagesHandlers;

use Illuminate\Support\Facades\Log;
use App\Services\Commercetools\CommercetoolsOrdersService;
use App\Services\Forter\ForterApiService;
use App\Models\Forter\ForterOrder;

class HandleOrderStateChangedMessage extends AbstractMessageHandler
{
    /**
     * Handle Message
     * @return void
     */
    protected function handleMessage()
    {
        // Pull fresh order from Commercetools
        $order = CommercetoolsOrdersService::getById($this->getResourceId());
        $order = ForterOrder::getInstance($order);

        // Send to v2/status
        return ForterApiService::makeOrderStatusRequest($order);
    }

    /**
     * @method getOldOrderState
     * @return string|null
     */
    public function getOldOrderState()
    {
        return $this->getMessageData('oldOrderState');
    }

    /**
     * @method getNewOrderState
     * @return string|null
     */
    public function getNewOrderState()
    {
        return $this->getMessageData('orderState');
    }


    /**
     * @method logHandleEnd
     * @return void
     */
    protected function logHandleEnd()
    {
        Log::info("[CommercetoolsMessagesHandlers\HandleOrderStateChangedMessage] [END] | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()} Order ID: {$this->getResourceId()} | Old Order State: {$this->getOldOrderState()} | New Order State: {$this->getNewOrderState()}");
    }
}
