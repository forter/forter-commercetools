<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Services\MessagingServices\Processors;

// Commercetools Extensions Handlers Jobs:
use App\Jobs\CommercetoolsMessagesHandlers\HandleOrderCreatedMessage;
use App\Jobs\CommercetoolsMessagesHandlers\HandleOrderPaymentAddedMessage;
use App\Jobs\CommercetoolsMessagesHandlers\HandleOrderStateChangedMessage;
use App\Jobs\CommercetoolsMessagesHandlers\HandlePaymentCreatedMessage;
use App\Jobs\CommercetoolsMessagesHandlers\HandlePaymentTransactionAddedMessage;

abstract class AbstractMessagingServiceProcessor
{
    /**
     * @var array
     */
    protected static $messages = [];

    /**
     * @method process
     * @return array  Messages
     */
    abstract protected static function process();

    /**
     * @method getHandlerClassForMessageType
     * @param  string                        $messageType
     * @return string|null
     */
    public static function getHandlerClassForMessageType($messageType)
    {
        switch ($messageType) {
            case 'OrderCreated':
                return HandleOrderCreatedMessage::class;
                break;

            case 'OrderPaymentAdded':
                return HandleOrderPaymentAddedMessage::class;
                break;

            case 'OrderStateChanged':
                return HandleOrderStateChangedMessage::class;
                break;

            case 'PaymentTransactionAdded':
                return HandlePaymentTransactionAddedMessage::class;
                break;
        }

        return null;
    }
}
