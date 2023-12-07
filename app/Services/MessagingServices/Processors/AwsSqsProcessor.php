<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Services\MessagingServices\Processors;

use Illuminate\Support\Facades\Log;
use App\Services\MessagingServices\Aws\SqsService;
use App\Helpers\UtilsHelper;
use App\Models\Forter\ForterCommercetoolsMessage;
use App\Services\MessagingServices\Processors\AbstractMessagingServiceProcessor;

class AwsSqsProcessor extends AbstractMessagingServiceProcessor
{
    /**
     * @method process
     */
    public static function process()
    {
        $i = 0;
        do {
            $i++;
            $result = SqsService::getMessages();
            $messages = $result->get('Messages') ?: [];
            $messages = UtilsHelper::toArrayRecursive($messages);
            $messagesCount = count($messages);
            Log::info("[AwsSqsProcessor::process] Received {$messagesCount} SQS messages.");
            Log::debug('[AwsSqsProcessor::process]', ['messages_count' => $messagesCount, 'sqs_result' => $result->toArray()]);

            foreach ($messages as $message) {
                try {
                    self::$messages[] = $message;
                    $sqsMessageId = $message['MessageId'];
                    $sqsMessageReceiptHandle = $message['ReceiptHandle'];
                    $payload = json_decode($message['Body'], true);
                    $messageModel = ForterCommercetoolsMessage::getInstance($payload);
                    if (
                        !$messageModel->getMessageId() ||   // Message ID
                        !$messageModel->getMessageType() || // Message Type
                        !$messageModel->getResourceId() ||  // Resource ID
                        !$messageModel->getResourceType()   // Resource Type
                    ) {
                        Log::warning("[AwsSqsProcessor::process] [SKIPPING] Invalid message payload - missing required fields | SQS Message ID:{$sqsMessageId} | SQS Receipt Handle:{$sqsMessageReceiptHandle}", ['message' => $message]);
                    } else {
                        Log::info("[AwsSqsProcessor::process] Handling Message Type:{$messageModel->getMessageType()} | Resource ID:{$messageModel->getResourceId()} | Resource Type:{$messageModel->getResourceType()} | Commercetools Message ID:{$messageModel->getMessageId()} | SQS Message ID:{$sqsMessageId} | SQS Receipt Handle:{$sqsMessageReceiptHandle}");

                        if (($handlerClass = self::getHandlerClassForMessageType($messageModel->getMessageType()))) {
                            UtilsHelper::dispatchJob($handlerClass, [$messageModel]);
                        } else {
                            Log::notice("[AwsSqsProcessor::process] No handler for message type `{$messageModel->getMessageType()}` - doing nothing | Commercetools Message ID:{$messageModel->getMessageId()} | SQS Message ID:{$sqsMessageId}");
                        }
                    }

                    // Delete message from SQS after process
                    SqsService::deleteMessage($sqsMessageReceiptHandle);
                } catch (\Exception $e) {
                    Log::error("[AwsSqsProcessor::process] [ERROR] {$e->getMessage()}", ['exception' => $e, 'message' => $message]);
                }
            }
        } while ($i < 4 && count($messages) > 0 && count(self::$messages) < 41);

        return self::$messages;
    }
}
