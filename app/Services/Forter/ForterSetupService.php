<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Services\Forter;

use Illuminate\Support\Facades\Log;
use App\Services\MessagingServices\Aws\SqsService;
use App\Services\Commercetools\CommercetoolsCartsService;
use App\Services\Commercetools\CommercetoolsExtensionsService;
use App\Services\Commercetools\CommercetoolsOrdersService;
use App\Services\Commercetools\CommercetoolsPaymentsService;
use App\Services\Commercetools\CommercetoolsSubscriptionsService;
use App\Services\Commercetools\CommercetoolsTypesService;
use App\Services\Forter\ForterApiService;
use App\Helpers\UtilsHelper;

class ForterSetupService
{
    public const FORTER_CUSTOM_TYPE_KEY = 'forterInfo';
    public const FORTER_API_EXTENSION_KEY = 'forter-commercetools-app';
    public const FORTER_SUBSCRIPTION_KEY = 'forter-commercetools-app';

    /**
     * @method validateForterCredentials
     * @return bool
     */
    public static function isValidForterCredentials()
    {
        try {
            foreach ([
                'forter.site_id',
                'forter.secret_key',
                'forter.api_version',
                'forter.extver',
            ] as $configKey => $configValue) {
                if (!config($configValue, false)) {
                    throw new \Exception("Missing required Forter config value: `{$configKey}`");
                }
            }
            $result = ForterApiService::validateCredentials();
            return $result->getStatus() === 'success';
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::isValidForterCredentials] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @method isValidCommercetoolsCredentials
     * @return bool
     */
    public static function isValidCommercetoolsCredentials()
    {
        try {
            foreach ([
                'commercetools.project_key',
                'commercetools.client_id',
                'commercetools.client_secret',
                'commercetools.region',
                'commercetools.auth_url',
                'commercetools.api_url',
                'commercetools.scopes',
            ] as $configKey => $configValue) {
                if (!config($configValue, false)) {
                    throw new \Exception("Missing required Commercetools config value: `{$configKey}`");
                }
            }
            $orders = CommercetoolsOrdersService::get(null, 1)->getCount();
            $carts = CommercetoolsCartsService::get(null, 1)->getCount();
            $payments = CommercetoolsPaymentsService::get(null, 1)->getCount();
            $types = CommercetoolsTypesService::get(null, 1)->getCount();
            $extensions = CommercetoolsExtensionsService::get(null, 1)->getCount();
            $subscriptions = CommercetoolsSubscriptionsService::get(null, 1)->getCount();
            return true;
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::isValidCommercetoolsCredentials] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @method getCurrentConfigSummary
     * @return array
     */
    public static function getCurrentConfigSummary()
    {
        return [
            [
                "Forter app is enabled" => config('forter.is_enabled'),
                "Forter site ID" => config('forter.site_id'),
                "Forter secret key" => substr(config('forter.secret_key'), 0, 6) . '**********',
                "Forter API version" => config('forter.api_version'),
                "Forter extver" => config('forter.extver'),
                "Forter pre-auth order validation enabled" => config('forter.pre_order_validation_enabled') ? 'true' : 'false',
                "Forter post-auth order validation enabled" => config('forter.post_order_validation_enabled') ? 'true' : 'false',
                "Forter order validation - require payment transaction" => config('forter.post_order_validation_require_payment_transaction') ? 'true' : 'false',
                "Forter decision actions - approve - pre" => config('forter.decision_actions.approve.pre', 'DO_NOTHING'),
                "Forter decision actions - approve - post" => config('forter.decision_actions.approve.post', 'DO_NOTHING'),
                "Forter decision actions - decline - pre" => config('forter.decision_actions.decline.pre', 'DO_NOTHING'),
                "Forter decision actions - decline - post" => config('forter.decision_actions.decline.post', 'DO_NOTHING'),
                "Forter decision actions - not reviewed - pre" => config('forter.decision_actions.not_reviewed.pre', 'DO_NOTHING'),
                "Forter decision actions - not reviewed - post" => config('forter.decision_actions.not_reviewed.post', 'DO_NOTHING'),
            ],
            [
                "Commercetools project key" => config('commercetools.project_key'),
                "Commercetools client ID" => substr(config('commercetools.client_id'), 0, 6) . '**********',
                "Commercetools client secret" => substr(config('commercetools.client_secret'), 0, 6) . '**********',
                "Commercetools region" => config('commercetools.region'),
                "Commercetools auth URL" => config('commercetools.auth_url'),
                "Commercetools API URL" => config('commercetools.api_url'),
                "Commercetools scopes" => config('commercetools.scopes'),
            ],
            [
                "Messaging service pull enabled" => config('forter.messaging_service_pull_enabled') ? 'true' : 'false',
                "Messaging service pull frequency" => config('forter.messaging_service_pull_frequency'),
                "Messaging service type" => config('forter.messaging_service_type'),
            ],
        ];
    }

    //============================================================//

    /**
     * @method getCustomType
     * @return array|null
     */
    public static function getCustomType()
    {
        $customType = CommercetoolsTypesService::getByKey(self::FORTER_CUSTOM_TYPE_KEY);
        return $customType && $customType->getKey() ? UtilsHelper::toArrayRecursive($customType) : null;
    }

    /**
     * @method createOrUpdateCustomType
     * @return array
     */
    public static function createOrUpdateCustomType(array $customType = null)
    {
        try {
            $fieldDefinitions = [];
            foreach ([
                'forterDecision' => 'Forter Decision',
                'forterReason' => 'Forter Reason',
                'forterRecommendations' => 'Forter Recommendations',
                'forterResponse' => 'Forter Response',
                'forterToken' => 'Forter Token (cookie)',
                'customerIP' => 'Customer IP',
                'customerUserAgent' => 'Customer User-Agent',
            ] as $fieldName => $fieldLabel) {
                $fieldDefinitions[$fieldName] = [
                    "type" => ["name" => "String"],
                    "name" => $fieldName,
                    "label" => ['en' => $fieldLabel],
                    "required" => false,
                    "inputHint" => $fieldName === 'forterResponse' ? 'MultiLine' : 'SingleLine',
                ];
            }

            $customTypeDetails = [
                'key' => self::FORTER_CUSTOM_TYPE_KEY,
                'name' => ['en' => 'Forter Info'],
                'description' => ['en' => 'Forter Info'],
                'resourceTypeIds' => ['order', 'payment', 'quote', 'transaction'],
                'fieldDefinitions' => $fieldDefinitions,
            ];

            // Update if already exist with the same key
            $customType = $customType ?: self::getCustomType();
            if ($customType && !empty($customType['key'])) {
                // Update existing extension
                $updateActions = [
                    [ // Name
                        'action' => 'changeName',
                        'name' => $customTypeDetails['name'],
                    ],
                    [ // Description
                        'action' => 'setDescription',
                        'description' => $customTypeDetails['description'],
                    ],
                ];
                // Check field definitions
                $existingFields = [];
                foreach ($customType['fieldDefinitions'] as $existingField) {
                    $existingFields[$existingField['name']] = $existingField;
                }
                foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
                    // Add missing fields or update existing field labels
                    if (!isset($existingFields[$fieldName])) {
                        $updateActions[] = [
                            'action' => 'addFieldDefinition',
                            'fieldDefinition' => $fieldDefinition,
                        ];
                    } else {
                        $updateActions[] = [
                            'action' => 'changeLabel',
                            'fieldName' => $fieldName,
                            'label' => $fieldDefinition['label'],
                        ];
                    }
                }
                // Set fields order if needed
                foreach ($existingFields as $fieldName => $existingField) {
                    if (!isset($fieldDefinitions[$fieldName])) {
                        $fieldDefinitions[$fieldName] = $existingField;
                    }
                }
                if (array_keys($existingFields) !== array_slice(array_keys($fieldDefinitions), 0, count($existingFields))) {
                    $updateActions[] = [
                        'action' => 'changeFieldDefinitionOrder',
                        'fieldNames' => array_keys($fieldDefinitions),
                    ];
                }
                $customType = CommercetoolsTypesService::updateByKey(
                    $customTypeDetails['key'], // Forter Custom Type Key
                    $updateActions,                    // Update Actions
                    $customType['version']             // Version
                );
            } else {
                // Create extension
                $customType = CommercetoolsTypesService::create(
                    $customTypeDetails['key'],              // Key
                    $customTypeDetails['name'],             // Name
                    $customTypeDetails['description'],      // Description
                    $customTypeDetails['resourceTypeIds'],  // Resource Type IDs
                    $customTypeDetails['fieldDefinitions'], // Field Definitions
                );
            }

            return UtilsHelper::toArrayRecursive($customType);
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::createOrUpdateCustomType] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @method deleteCustomType
     */
    public static function deleteCustomType()
    {
        try {
            if (!($customType = self::getCustomType())) {
                return;
            }
            return CommercetoolsTypesService::deleteByKey(self::FORTER_CUSTOM_TYPE_KEY, $customType['version']);
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::deleteCustomType] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @method extractCustomTypeSummary
     */
    public static function extractCustomTypeSummary(array $customType)
    {
        $fields = [];
        foreach ($customType['fieldDefinitions'] as $field) {
            $fields[] = $field['name'];
        }
        return [
            'fields' => implode(', ', $fields),
        ];
    }

    //============================================================//

    /**
     * @method getApiExtension
     * @return array|null
     */
    public static function getApiExtension()
    {
        $extension = CommercetoolsExtensionsService::getByKey(self::FORTER_API_EXTENSION_KEY);
        return $extension && $extension->getKey() ? UtilsHelper::toArrayRecursive($extension) : null;
    }

    /**
     * @method createOrUpdateApiExtension
     * @return array
     */
    public static function createOrUpdateApiExtension(array $extension = null)
    {
        try {
            $extensionDetails = [
                'key' => self::FORTER_API_EXTENSION_KEY,
                'destination' => [
                    'type' => 'HTTP',
                    'url' => config('app.url') . '/commercetools/api/extensions',
                    'authentication' => [
                      'type' => 'AuthorizationHeader',
                      'headerValue' => 'Basic ' . UtilsHelper::getCommercetoolsApiExtensionsBasicAuthSecret(),
                    ]
                ],
                'triggers' => [
                    [
                        "resourceTypeId" => "order",
                        "actions" => ["Create"],
                    ]
                ],
                'timeoutInMs' => 2000, // 10000, // 10000 should be used if possible
            ];

            // Update if already exist with the same key
            $extension = $extension ?: self::getApiExtension();
            if ($extension && !empty($extension['key'])) {
                // Update existing extension
                $extension = CommercetoolsExtensionsService::updateByKey(
                    self::FORTER_API_EXTENSION_KEY, // Forter API extension key
                    [
                        [ // Destination
                            'action' => 'changeDestination',
                            'destination' => $extensionDetails['destination'],
                        ],
                        [ // Triggers
                            'action' => 'changeTriggers',
                            'triggers' => $extensionDetails['triggers'],
                        ],
                        [ // Timeout (milliseconds)
                            'action' => 'setTimeoutInMs',
                            'timeoutInMs' => $extensionDetails['timeoutInMs'],
                        ],
                    ],
                    $extension['version'] // Version
                );
            } else {
                // Create extension
                $extension = CommercetoolsExtensionsService::create(
                    $extensionDetails['key'],         // Forter API extension key
                    $extensionDetails['destination'], // Destination
                    $extensionDetails['triggers'],    // Triggers
                    $extensionDetails['timeoutInMs'], // Timeout (milliseconds)
                );
            }

            return UtilsHelper::toArrayRecursive($extension);
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::createOrUpdateApiExtension] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @method deleteApiExtension
     */
    public static function deleteApiExtension()
    {
        try {
            if (!($extension = self::getApiExtension())) {
                return;
            }
            return CommercetoolsExtensionsService::deleteByKey(self::FORTER_API_EXTENSION_KEY, $extension['version']);
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::deleteApiExtension] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @method extractExtensionSummary
     */
    public static function extractExtensionSummary(array $extension)
    {
        $triggers = [];
        foreach ($extension['triggers'] as $trigger) {
            foreach ($trigger['actions'] as $action) {
                $triggers[] = ucfirst($trigger['resourceTypeId']) . '/' . $action;
            }
        }
        return [
            'destination' => $extension['destination']['url'],
            'triggers' => implode(', ', $triggers),
        ];
    }

    //============================================================//

    /**
     * @method getSubscription
     * @return array|null
     */
    public static function getSubscription()
    {
        $subscription = CommercetoolsSubscriptionsService::getByKey(self::FORTER_SUBSCRIPTION_KEY);
        return $subscription && $subscription->getKey() ? UtilsHelper::toArrayRecursive($subscription) : null;
    }

    /**
     * @method createOrUpdateSubscription
     */
    public static function createOrUpdateSubscription(array $subscription = null)
    {
        try {
            if (UtilsHelper::isForterPostOrderValidationEnabled()) {
                $messages = [
                    [
                        "resourceTypeId" => "order",
                        "types" => [
                            "OrderCreated",
                            "OrderPaymentAdded",
                            "OrderStateChanged",
                        ],
                    ],
                    [
                        "resourceTypeId" => "payment",
                        "types" => [
                            "PaymentTransactionAdded",

                        ],
                    ],
                ];
            } else {
                $messages = [
                    [
                        "resourceTypeId" => "order",
                        "types" => [
                            "OrderStateChanged",
                        ],
                    ],
                ];
            }

            $subscriptionDetails = [
                'key' => self::FORTER_SUBSCRIPTION_KEY,
                'destination' => [
                    "type" => "SQS",
                    "queueUrl" => config('forter.messaging_services.sqs.queue_url'),
                    "authenticationMode" => "IAM",
                    "region" => config('forter.messaging_services.sqs.region')
                ],
                'messages' => $messages,
            ];

            // Update if already exist with the same key
            $subscription = $subscription ?: self::getSubscription();
            if ($subscription && !empty($subscription['key'])) {
                // Update existing subscription
                $subscription = CommercetoolsSubscriptionsService::updateByKey(
                    self::FORTER_API_EXTENSION_KEY, // Forter subscription key
                    [
                        [ // Destination
                            'action' => 'changeDestination',
                            'destination' => $subscriptionDetails['destination'],
                        ],
                        [ // Messages
                            'action' => 'setMessages',
                            'messages' => $subscriptionDetails['messages'],
                        ],
                    ],
                    $subscription['version'] // Version
                );
            } else {
                // Create subscription
                $subscription = CommercetoolsSubscriptionsService::create(
                    $subscriptionDetails['key'],          // Forter subscription key
                    $subscriptionDetails['destination'],  // Destination,
                    $subscriptionDetails['messages']      // Messages
                );
            }

            return UtilsHelper::toArrayRecursive($subscription);
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::createOrUpdateSubscription] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @method deleteSubscription
     */
    public static function deleteSubscription()
    {
        try {
            if (!($subscription = self::getSubscription())) {
                return;
            }
            return CommercetoolsSubscriptionsService::deleteByKey(self::FORTER_SUBSCRIPTION_KEY, $subscription['version']);
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::deleteSubscription] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * @method extractSubscriptionSummary
     */
    public static function extractSubscriptionSummary(array $subscription)
    {
        $messages = [];
        foreach ($subscription['messages'] as $message) {
            foreach ($message['types'] as $type) {
                $messages[] = $type;
            }
        }
        return [
            'destination' => "Type: " . $subscription['destination']['type'] . " | " . "Queue URL: " . $subscription['destination']['queueUrl'] . " | " . "Region: " . $subscription['destination']['region'] . " | " . "Authentication Mode: " . $subscription['destination']['authenticationMode'],
            'messages' => implode(', ', $messages),
        ];
    }

    //============================================================//

    /**
     * @method prepareAwsSqs
     * @return array
     */
    public static function prepareAwsSqs()
    {
        try {
            foreach ([
                'forter.messaging_services.sqs.key',
                'forter.messaging_services.sqs.secret',
                'forter.messaging_services.sqs.queue_url',
                'forter.messaging_services.sqs.region',
            ] as $configKey => $configValue) {
                if (!config($configValue, false)) {
                    throw new \Exception("Missing required Amazon-SQS config value: `{$configKey}`");
                }
            }
            $response = SqsService::setQueueLongPolling(20);
            return UtilsHelper::toArrayRecursive($response);
        } catch (\Exception $e) {
            Log::error("[ForterSetupService::prepareAwsSqs] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
