<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Services\MessagingServices\Aws;

use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

class SqsService
{
    public static function getSqsClient()
    {
        return new SqsClient(
            [
                'region' => config('forter.messaging_services.sqs.region'),
                'version' => 'latest',
                'http' => [
                    'timeout' => 60,
                    'connect_timeout' => 60,
                ],
                'credentials' => [
                    'key' => config('forter.messaging_services.sqs.key'),
                    'secret' => config('forter.messaging_services.sqs.secret'),
                ],
            ]
        );
    }

    public static function setQueueLongPolling($waitTimeSeconds = 20, $visibilityTimeout = 120)
    {
        return self::getSqsClient()->setQueueAttributes(
            [
                'Attributes' => [
                    'ReceiveMessageWaitTimeSeconds' => (string) $waitTimeSeconds,
                    'VisibilityTimeout' => (string) $visibilityTimeout,
                ],
                'QueueUrl' => config('forter.messaging_services.sqs.queue_url')
            ]
        );
    }

    public static function getMessages($maxNumberOfMessages = 10, $waitTimeSeconds = 20, $visibilityTimeout = 120)
    {
        return self::getSqsClient()->receiveMessage(
            [
                'AttributeNames' => ['All'],
                'MaxNumberOfMessages' => $maxNumberOfMessages,
                'MessageAttributeNames' => ['All'],
                'WaitTimeSeconds' => $waitTimeSeconds,
                'VisibilityTimeout' => $visibilityTimeout,
                'QueueUrl' => config('forter.messaging_services.sqs.queue_url')
            ]
        );
    }

    public static function sendMessage($messageBody, $messageAttributes = [], $delaySeconds = 0)
    {
        return self::getSqsClient()->sendMessage([
            'DelaySeconds' => $delaySeconds,
            'MessageAttributes' => $messageAttributes,
            'MessageBody' => $messageBody,
            'QueueUrl' => config('forter.messaging_services.sqs.queue_url'),
        ]);
    }

    public static function deleteMessage($sqsMessageReceiptHandle)
    {
        return self::getSqsClient()->deleteMessage([
            'QueueUrl' => config('forter.messaging_services.sqs.queue_url'),
            'ReceiptHandle' => $sqsMessageReceiptHandle,
        ]);
    }
}
