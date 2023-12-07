<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Helpers\Forter\ForterTestHelper;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterResponse;

class UtilsHelper
{
    public const APP_IS_DISABLED_MSG = 'Forter app is currently disabled by configuration';

    /**
     * @method isForterEnabled
     * @return bool
     */
    public static function isForterEnabled()
    {
        return (bool) config('forter.is_enabled');
    }
    /**
     * @method throwIfForterIsDisabled
     * @return void
     */
    public static function throwIfForterIsDisabled()
    {
        if (!self::isForterEnabled()) {
            throw new \Exception(self::APP_IS_DISABLED_MSG);
        }
    }

    /**
     * @method toArrayRecursive
     * @param  mixed           $var
     * @return array
     */
    public static function toArrayRecursive($var)
    {
        return (array) \json_decode(\json_encode($var), true);
    }

    public static function addFractionDigitsToNumber($number, $fractionDigits)
    {
        if (!$fractionDigits) {
            return $number;
        }
        return $number / pow(10, $fractionDigits);
    }

    public static function getCommercetoolsApiExtensionsBasicAuthSecretForHashing()
    {
        return hash('sha256', hash('sha256', \json_encode([
            config('commercetools.client_id'),
            substr(config('commercetools.client_secret'), 2, 10),
            config('forter.site_id'),
            substr(config('forter.secret_key'), 2, 10),
        ])));
    }

    /**
     * Get base64-encoded hash for
     * @method getCommercetoolsApiExtensionsBasicAuthSecret
     * @return [type]
     */
    public static function getCommercetoolsApiExtensionsBasicAuthSecret()
    {
        return config('commercetools.api_extensions_basic_auth_secret', \base64_encode(
            self::getCommercetoolsApiExtensionsBasicAuthSecretForHashing()
        ));
    }

    /**
     * @method checkCommercetoolsApiExtensionsBasicAuthSecret
     * @param  string                                         $secret
     * @return bool
     */
    public static function checkCommercetoolsApiExtensionsBasicAuthSecret($secret)
    {
        $hash = \trim(\preg_replace('/^\s*Basic\s*/msi', '', $secret));
        return $hash === self::getCommercetoolsApiExtensionsBasicAuthSecret();
    }

    /**
     * @method isForterPreOrderValidationEnabled  (pre auth)
     * @return bool
     */
    public static function isForterPreOrderValidationEnabled()
    {
        return (bool) config('forter.pre_order_validation_enabled', true);
    }

    /**
     * @method isForterPostOrderValidationEnabled   (post auth)
     * @return bool
     */
    public static function isForterPostOrderValidationEnabled()
    {
        return (bool) config('forter.post_order_validation_enabled', true);
    }

    /**
     * @method isForterPostOrderValidationRequirePaymentTransaction
     * @return bool
     */
    public static function isForterPostOrderValidationRequirePaymentTransaction()
    {
        return (bool) config('forter.post_order_validation_require_payment_transaction', true);
    }

    /**
     * Get the Forter decision action from config
     * @method getForterDecisionActionConfig
     * @param  string                        $forterDecision
     * @param  string                        $authStep  ('pre'/'post')
     * @param  string                        $default   (Default: 'DO_NOTHING')
     * @return string
     */
    public static function getForterDecisionActionConfig($forterDecision, $authStep, $default = 'DO_NOTHING')
    {
        return config(sprintf('forter.decision_actions.%s.%s', preg_replace('/\s+/', '_', $forterDecision), $authStep), $default);
    }

    /**
     * Returns 'dispatchSync' or 'dispatch' (if queue is enabled on forter config)
     * @method getJobsDispatchMethod
     * @return string
     */
    public static function getJobsDispatchMethod()
    {
        return !config('forter.use_async_queue_for_jobs', false) ? 'dispatchSync' : 'dispatch';
    }

    /**
     * @method dispatchJob
     * @param  string                   $jobClass
     * @param  array                    $payload
     * @return mixed
     */
    public static function dispatchJob($jobClass, $params = [])
    {
        return call_user_func_array($jobClass .'::' . self::getJobsDispatchMethod(), $params);
    }

    /**
     * Checks if CT has required credentials for SQS messaging service (without checking the connection)
     * @method hasCommercetoolsSqsCredentials
     * @return boolean
     */
    public static function hasCommercetoolsSqsCredentials()
    {
        return config('forter.messaging_services.sqs.region') &&
            config('forter.messaging_services.sqs.key') &&
            config('forter.messaging_services.sqs.secret') &&
            config('forter.messaging_services.sqs.queue_url');
    }

    /**
     * @method getMessagingServiceType
     * @return string
     */
    public static function getMessagingServiceType()
    {
        return config('forter.messaging_service_type', 'sqs');
    }

    /**
     * @method getMessagingServiceConfig
     * @return array|null
     */
    public static function getMessagingServiceConfig()
    {
        return config(sprintf('forter.messaging_services.%s', self::getMessagingServiceType()));
    }

    /**
     * @method getMessagingServicePullEnabled
     * @return bool
     */
    public static function getMessagingServicePullEnabled()
    {
        return (bool) config('forter.messaging_service_pull_enabled', true);
    }

    /**
     * @method getMessagingServicePullFrequency
     * @return bool
     */
    public static function getMessagingServicePullFrequency()
    {
        return config('forter.messaging_service_pull_frequency', '* * * * *');
    }

    /**
     * @method isEnabledTestMissingDataMocking
     * @return bool
     */
    public static function isEnabledTestMissingDataMocking()
    {
        return (bool) config('forter.test.missing_data_mocking_enabled', false);
    }

    /**
     * @method maybeMockMissingDataIfEnabledForTesting
     * @param  array|ForterOrder                       $order
     * @param  boolean                                 $withVerificationResults
     * @return array|ForterOrder
     */
    public static function maybeMockMissingDataIfEnabledForTesting($order, $withVerificationResults = false)
    {
        if (self::isEnabledTestMissingDataMocking()) {
            $order = ForterTestHelper::mockMissignOrderAndPaymentData($order, $withVerificationResults);
        }
        return $order;
    }

    /**
     * @method getRecommendationHandlerClass
     * @param  string                           $recommendation
     * @return string|null
     */
    public static function getRecommendationHandlerClass($recommendation)
    {
        return config(sprintf('forter.recommendation_handlers.%s', $recommendation));
    }

    /**
     * @method maybeCallForterRecommendationHandler
     * @param  string                            $recommendation
     * @param  ForterResponse                    $forterResponse
     * @param  ForterOrder                       $order
     * @param  string                            $authStep
     * @return array
     */
    public static function maybeCallForterRecommendationHandler($recommendation, ForterResponse $forterResponse, ForterOrder $order, $authStep)
    {
        $handlerActions = [];
        if (($className = self::getRecommendationHandlerClass($recommendation)) && class_exists($className)) {
            $recommendationHandler = new $className($recommendation, $forterResponse, $order, $authStep);
            $handlerActions = $recommendationHandler->handle();
            Log::info("[Recommendation handler called] Recommendation: {$recommendation} | Order ID: {$order->getForterOrderId()} | Forter decision: {$forterResponse->getDecision()} | Auth step: {$authStep}", ['recommendation_handler_actions' => $handlerActions]);
        }
        return $handlerActions;
    }

    /**
     * @method maybeHandleForterRecommendations
     * @param  ForterResponse                    $forterResponse
     * @param  ForterOrder                       $order
     * @param  string                            $authStep
     * @return array
     */
    public static function maybeHandleForterRecommendations(ForterResponse $forterResponse, ForterOrder $order, $authStep)
    {
        $handlersActions = [];
        if (($recommendations = $forterResponse->getRecommendations())) {
            foreach ($recommendations as $recommendation) {
                if (!$recommendation || !is_string($recommendation)) {
                    continue;
                }
                $handlerActions = UtilsHelper::maybeCallForterRecommendationHandler($recommendation, $forterResponse, $order, $authStep);
                $handlersActions = array_merge_recursive($handlersActions, $handlerActions);
            }
        }
        return $handlersActions;
    }
}
