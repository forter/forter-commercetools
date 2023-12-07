<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Services\Forter\SchemaBuilders;

use Illuminate\Support\Facades\Log;
use App\Models\Forter\ForterCommercetoolsModel;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterResponse;
use App\Services\Forter\ForterSetupService;
use App\Helpers\UtilsHelper;

class ForterDecisionActionsBuilder
{
    public const FORTER_CUSTOM_TYPE_KEY = ForterSetupService::FORTER_CUSTOM_TYPE_KEY; // 'forterInfo'

    public const FORTER_CUSTOM_TYPE_FIELDS = [
        'forterResponse',
        'forterDecision',
        'forterReason',
        'forterRecommendations',
        'forterToken',
        'customerIP',
        'customerUserAgent'
    ];

    /**
     * @method buildCommercetoolsDecisionActions
     * returns ['actions' => [[...],[...],...]] or ['errors' => [[...],[...],...]]
     * @param  ForterOrder                       $order
     * @param  string                            $authStep
     * @param  ForterResponse                    $forterResponse
     * @return array
     */
    public static function buildCommercetoolsDecisionActions(ForterOrder $order, $authStep, ForterResponse $forterResponse)
    {
        // Build default custom fields actions
        $customFields = array_merge(
            self::getOrderForterInfoCustomFields($order), // Get current fields (so it won't be overriden)
            self::buildCustomFieldsFromForterResponse($order, $authStep, $forterResponse),
            self::buildCustomFieldsFromCartToOrder($order),
        );


        // Maybe handle recommendations
        $recommendationHandlerResult = (array) UtilsHelper::maybeHandleForterRecommendations($forterResponse, $order, $authStep);

        if ($authStep === 'pre') {
            // If recommendation handler responded with errors - block order (only on pre auth).
            if (!empty($recommendationHandlerResult['errors'])) {
                return [
                    "errors" => $recommendationHandlerResult['errors'],
                ];
            }
            // If configured - block order on decline decision (only on pre auth).
            $forterDecisionAction = UtilsHelper::getForterDecisionActionConfig($forterResponse->getDecision(), $authStep);
            if ($forterResponse->getDecision() === 'decline' && $forterDecisionAction === 'BLOCK_ORDER_PLACE') {
                return [
                    "errors" => [[
                        "code" => "InvalidOperation",
                        "message" => config('forter.pre_decline_block_order_place_error_message', 'We are sorry, but we could not process your order at this time.'),
                    ]],
                ];
            }
        }

        unset($recommendationHandlerResult['errors']); // Unset errors if set on post auth

        // Deal with recommendation based actions
        if (!empty($recommendationHandlerResult['actions'])) {
            if (!empty($recommendationHandlerResult['only'])) {
                // Override default/configured decision actions
                $configuredDecisionBasedActions = $recommendationHandlerResult['actions'];
            } else {
                // Add to default/configured decision actions
                $configuredDecisionBasedActions = array_merge(
                    self::buildDecisionSpecificCommercetoolsOrderActions($forterResponse->getDecision(), $authStep),
                    $recommendationHandlerResult['actions']
                );
            }
        } else {
            // Default: use default/configured decision actions
            $configuredDecisionBasedActions = self::buildDecisionSpecificCommercetoolsOrderActions($forterResponse->getDecision(), $authStep);
        }

        // Build final decision actions
        return [
            'actions' => array_merge(
                self::buildCommercetoolsCustomTypeActions($customFields),
                $configuredDecisionBasedActions
            )
        ];
    }

    public static function buildCommercetoolsCustomTypeActions($customFields = [], $customType = self::FORTER_CUSTOM_TYPE_KEY)
    {
        return [
            [
                "action" => "setCustomType",
                "type" => [
                    "key" => $customType ?: self::FORTER_CUSTOM_TYPE_KEY,
                    "typeId" => "type",
                ],
                "fields" => $customFields,
            ],
        ];
    }

    public static function buildCommercetoolsSetCustomFieldActions($customFieldsData)
    {
        $actions = [];

        foreach ($customFieldsData as $fieldName => $fieldValue) {
            $actions[] = [
                "action" => "setCustomField",
                "name" => $fieldName,
                "value" => $fieldValue
            ];
        }

        return $actions;
    }

    /**
     * @method getOrderForterInfoCustomFields
     * @param  ForterOrder                    $order
     * @return array
     */
    public static function getOrderForterInfoCustomFields(ForterOrder $order)
    {
        $customFields = [];
        $orderCustomFields = $order->getCustomFields();

        foreach (self::FORTER_CUSTOM_TYPE_FIELDS as $fieldName) {
            $customFields[$fieldName] = !empty($orderCustomFields[$fieldName]) ? $orderCustomFields[$fieldName] : '';
        }

        return $customFields;
    }

    /**
     * @method buildCustomFieldsFromForterResponse
     * @param  ForterCommercetoolsModel                    $forterCtModel  CT order (or payment/cart/transation/...)
     * @param  string                                      $authStep   ('pre'/'post')
     * @param  ForterResponse                              $forterResponse
     * @param  bool                                        $returnActions  (default:false)
     * @return array
     */
    public static function buildCustomFieldsFromForterResponse(ForterCommercetoolsModel $forterCtModel, $authStep, ForterResponse $forterResponse, $returnActions = false)
    {
        $customFields = [];
        $forterDecision = $forterResponse->getDecision();

        // Append current Forter response to previous responses
        $forterResponses = $forterCtModel->getForterResponse(true);
        $forterResponses[$authStep . '_auth_' . now()->format('YmdHis')] = $forterResponse->getResponseData();

        $customFields = [
            'forterResponse' => json_encode($forterResponses),
            'forterDecision' => $forterDecision,
            'forterReason' => $forterResponse->getReasonCode(),
        ];

        if (($forterRecommendations = $forterResponse->getRecommendationMessages())) {
            $customFields['forterRecommendations'] = implode(', ', $forterRecommendations);
        }

        // Build Commercetools custom-fields actions array
        $customFields = array_filter($customFields);
        return $returnActions ? self::buildCommercetoolsSetCustomFieldActions($customFields) : $customFields;
    }

    /**
     * Convert mandatory Forter token (cookie) & other custom fields from cart to order (if missing)
     * @method buildCustomFieldsFromCartToOrder
     * @param  array                                       $order
     * @param  bool                                        $returnActions  (default:false)
     * @return array
     */
    public static function buildCustomFieldsFromCartToOrder(ForterOrder $order, $returnActions = false)
    {
        $customFields = [];

        $cart = $order->getCart();
        if (!empty($cart->getCustomFields())) {
            foreach ([
                'forterToken',
                'customerIP',
                'customerUserAgent'
            ] as $fieldName) {
                if (
                    empty($order->getCustomField($fieldName)) &&
                    ($val = $cart->getCustomField($fieldName))
                ) {
                    $customFields[$fieldName] = $val;
                }
            }
        }

        // Build Commercetools custom-fields actions array or return fields
        $customFields = array_filter($customFields);
        return $returnActions ? self::buildCommercetoolsSetCustomFieldActions($customFields) : $customFields;
    }

    /**
     * @method buildDecisionSpecificCommercetoolsOrderActions
     * @param  string                                         $forterDecision
     * @param  string                                         $authStep
     * @return array
     */
    public static function buildDecisionSpecificCommercetoolsOrderActions($forterDecision, $authStep)
    {
        $actions = [];

        $forterDecisionAction = UtilsHelper::getForterDecisionActionConfig($forterDecision, $authStep);

        preg_match('/(SET_ORDER_STATE)\s*:\s*([^\s]+)/msi', $forterDecisionAction, $setOrderStateAction);
        if (!empty($setOrderStateAction[1]) && !empty($setOrderStateAction[2])) {
            $actions[] = [
                "action" => "changeOrderState",
                "orderState" => $setOrderStateAction[2],
            ];
        }

        return $actions;
    }
}
