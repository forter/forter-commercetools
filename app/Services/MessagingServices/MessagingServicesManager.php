<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Services\MessagingServices;

use Illuminate\Support\Facades\Log;
use App\Helpers\UtilsHelper;

// Messaging Service Processors:
use App\Services\MessagingServices\Processors\AwsSqsProcessor;

class MessagingServicesManager
{
    /**
     * @method pullAndProcessMessages
     * @return array  Messages
     */
    public static function pullAndProcessMessages()
    {
        if (!UtilsHelper::isForterEnabled()) {
            Log::warning('[Commercetools\Subscriptions\MessagesManager::pullAndProcessMessages] [SKIPPING] ' . UtilsHelper::APP_IS_DISABLED_MSG);
            return [];
        }
        if (!UtilsHelper::getMessagingServicePullEnabled()) {
            Log::warning('[Commercetools\Subscriptions\MessagesManager::pullAndProcessMessages] Messaging service pulling is disabled, please set `forter.messaging_service_pull_enabled` to `true` before dispatching this job.');
            return [];
        }

        switch (UtilsHelper::getMessagingServiceType()) {
            case 'sqs':
                return AwsSqsProcessor::process();
                break;

            // Support for other cases/services may be added in the future
        }
    }
}
