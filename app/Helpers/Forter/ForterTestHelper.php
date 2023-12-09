<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Helpers\Forter;

use App\Jobs\PullAndRouteCommercetoolsMessages;
use App\Services\Commercetools\CommercetoolsCartsService;
use App\Services\Commercetools\CommercetoolsOrdersService;
use App\Services\Commercetools\CommercetoolsPaymentsService;
use App\Services\Commercetools\CommercetoolsTypesService;
use App\Helpers\UtilsHelper;
use App\Models\Forter\ForterOrder;

class ForterTestHelper
{
    /**
     * @return array
     */
    public static function generateForterTestOrder($forterDecision, $paymentInterface, $paymentMethod, $paymentAdditionalInfo)
    {
        UtilsHelper::throwIfForterIsDisabled();

        self::createPaymentAdditionalInfoTypeIfNeeded();

        $uniqid = uniqid();
        $cart = CommercetoolsCartsService::replicateCart(config("forter.test.{$forterDecision}_order_id"), 'order');

        $currencyCode = $cart->getTaxedPrice()->getTotalGross()->getCurrencyCode();
        $centAmount = $cart->getTaxedPrice()->getTotalGross()->getCentAmount();
        $fractionDigits = $cart->getTaxedPrice()->getTotalGross()->getFractionDigits();

        //(string) UtilsHelper::addFractionDigitsToNumber($centAmount, $fractionDigits);

        $verificationResults = '';
        if (isset($paymentAdditionalInfo['verificationResults'])) {
            $verificationResults = $paymentAdditionalInfo['verificationResults'];
            unset($paymentAdditionalInfo['verificationResults']);
        }
        if (empty($paymentAdditionalInfo['nameOnCard'])) {
            $paymentAdditionalInfo['nameOnCard'] = "{$cart->getBillingAddress()->getFirstName()} {$cart->getBillingAddress()->getLastName()}";
        }

        $payment = CommercetoolsPaymentsService::create([
                "key" => "forter-test-payment-{$forterDecision}-auto-{$uniqid}",
                "interfaceId" => "forter-test-payment-{$paymentInterface}-auto-{$uniqid}",
                "amountPlanned" => [
                    "currencyCode" => $currencyCode,
                    "centAmount" => $centAmount
                ],
                "paymentMethodInfo" => [
                    "paymentInterface" => "{$paymentInterface}",
                    "method" => "{$paymentMethod}",
                    "name" => [
                      "en" => "{$paymentMethod}"
                    ]
                ],
                "transactions" => [

                ],
                "custom" => [
                    "type" => [
                        "key" => "paymentAdditionalInfo",
                        "typeId" => "type",
                    ],
                    "fields" => $paymentAdditionalInfo,
                ]
            ]);

        $cart = CommercetoolsCartsService::addPayment($cart, $payment->getId());

        $order = CommercetoolsOrdersService::create(
            $cart->getId(),
            $cart->getVersion(),
            "forter-test-{$forterDecision}-auto-{$uniqid}"
        );

        $order = CommercetoolsOrdersService::updateById(
            $order->getId(),
            [
                [
                    "action" => "changePaymentState",
                    "paymentState" => 'Paid'
                ],
            ],
            $order->getVersion()
        );

        $order = ForterOrder::getInstance($order);

        foreach ($order->getPayments() as $payment) {
            if (!empty($verificationResults)) {
                $payment = CommercetoolsPaymentsService::updateById(
                    $payment->getId(),
                    [
                        [
                            "action" => "setCustomField",
                            "name" => 'verificationResults',
                            "value" => $verificationResults,
                        ],
                    ],
                    $payment->getVersion()
                );
            }

            $paymentAdditionalInfo['verificationResults'] = $verificationResults;
            $payment = CommercetoolsPaymentsService::addPaymentTransaction(
                $payment->getId(),
                [
                    "timestamp" => now()->toIso8601String(),
                    "type" => "Charge",
                    "amount" => [
                        "currencyCode" => $currencyCode,
                        "centAmount" => $centAmount
                    ],
                    "state" => "Success",
                    "custom" => [
                        "type" => [
                            "key" => "paymentAdditionalInfo",
                            "typeId" => "type",
                        ],
                        "fields" => $paymentAdditionalInfo,
                    ],
                ],
                $payment->getVersion()
            );
        }

        // Wait for messages (post auth)
        sleep(10);
        UtilsHelper::dispatchJob(PullAndRouteCommercetoolsMessages::class);
        sleep(3);

        $order = CommercetoolsOrdersService::getById($order->getId(), ['paymentInfo.payments[*]', 'cart']);

        return UtilsHelper::toArrayRecursive($order);
    }

    /**
     * @method createPaymentAdditionalInfoTypeIfNeeded
     * @return void
     */
    public static function createPaymentAdditionalInfoTypeIfNeeded()
    {
        UtilsHelper::throwIfForterIsDisabled();

        $key = 'paymentAdditionalInfo';

        $fieldDefinitions = [];
        foreach ([
                'cardBin' => 'Card Bin',
                'cardBank' => 'Card Bank',
                'cardBrand' => 'Card Brand',
                'cardType' => 'Card Type',
                'countryOfIssuance' => 'Country of Issuance',
                'expirationMonth' => 'Card Expiration Month',
                'expirationYear' => 'Card Expiration Year',
                'lastFourDigits' => 'Card Last Four Digits',
                'fingerprint' => 'Fingerprint',
                'fullResponsePayload' => 'Full Response Payload',
                'nameOnCard' => 'Name on Card',
                'paymentProcessorData' => 'Payment Processor Data',
                'verificationResults' => 'Verification Results',
                //'cardExpMonth' => 'Card Expiration Month',
                //'cardExpYear' => 'Card Expiration Year',
                //'cardLastFour' => 'Card Last Four Digits',
            ] as $fieldName => $fieldLabel) {
            $fieldDefinitions[$fieldName] = [
                    "type" => ["name" => "String"],
                    "name" => $fieldName,
                    "label" => ['en' => $fieldLabel],
                    "required" => false,
                    "inputHint" => "SingleLine"
                ];
        }

        $customType = UtilsHelper::toArrayRecursive(CommercetoolsTypesService::getByKey($key));
        if ($customType && !empty($customType['key'])) {
            $updateActions = [];
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
            if ($updateActions) {
                $customType = CommercetoolsTypesService::updateByKey(
                    $customType['key'],
                    $updateActions,
                    $customType['version']
                );
            }
        } else {
            $type = CommercetoolsTypesService::create(
                $key,
                ['en' => 'Additional Info'],
                ['en' => 'Additional Info'],
                ['payment', 'transaction'],
                $fieldDefinitions,
            );
        }
    }

    /**
     * @method mockMissignOrderAndPaymentData
     * @param  array                          $order
     * @param  boolean                        $withVerificationResults
     * @return array|ForterOrder
     */
    public static function mockMissignOrderAndPaymentData($order, $withVerificationResults = false)
    {
        UtilsHelper::throwIfForterIsDisabled();

        $returnForterOrder = is_a($order, ForterOrder::class);
        $order = UtilsHelper::toArrayRecursive($order);

        $currencyCode = !empty($order['taxedPrice']['totalGross']['currencyCode']) ? $order['taxedPrice']['totalGross']['currencyCode'] : $order['totalPrice']['currencyCode'];
        $totalCentAmount = !empty($order['taxedPrice']['totalGross']['centAmount']) ? $order['taxedPrice']['totalGross']['centAmount'] : $order['totalPrice']['centAmount'];
        $totalFractionDigits = !empty($order['taxedPrice']['totalGross']['fractionDigits']) ? $order['taxedPrice']['totalGross']['fractionDigits'] : $order['totalPrice']['fractionDigits'];

        $mockOrderData = [
            "billingAddress" => [
                'email' => !empty($order['customerEmail']) ? $order['customerEmail'] : '',
            ],
            "shippingAddress" => [
                'email' => !empty($order['customerEmail']) ? $order['customerEmail'] : '',
            ],
            'shippingInfo' => [
                'shippingMethodName' => 'Standard Delivery',
                'price' => [
                    'type' => 'centPrecision',
                    'currencyCode' => !empty($order['taxedPrice']['totalGross']['currencyCode']) ? $order['taxedPrice']['totalGross']['currencyCode'] : $order['totalPrice']['currencyCode'],
                    'centAmount' => '000',
                    'fractionDigits' => 2,
                ],
            ],
            'custom' => [
                'fields' => [
                    'customerIP' => '127.0.0.1',
                    'forterToken' => 'be0b61b0586245bfa8993dc3e0a779c3_1701273498707__UDF43_15ck_tt',
                    'customerUserAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',

                ],
            ],
        ];

        $order = \array_replace_recursive($mockOrderData, $order);

        $paymentInterface = 'adyen';
        $paymentMethod = 'credit_card';
        $paymentAdditionalInfo = [
            'cardBin' => '411111',
            'cardBank' => 'Chase',
            'cardBrand' => 'VISA',
            'cardType' => 'CREDIT',
            'countryOfIssuance' => 'US',
            'expirationMonth' => '03',
            'expirationYear' => '2030',
            'lastFourDigits' => '1142',
            'fingerprint' => 'Xt5EWLLDS7FJjR1c',
            'fullResponsePayload' => '',
            'nameOnCard' => (!empty($order['billingAddress']['firstName']) ? $order['billingAddress']['firstName'] : 'Forter') . ' ' . (!empty($order['billingAddress']['lastName']) ? $order['billingAddress']['lastName'] : 'Tester'),
            'paymentProcessorData' => '{"processorMerchantId":"ncxwe5490asjdf","processorName":"Chase Paymentech","processorTransactionId":"fjdsS46sdklFd20"}',
        ];

        if ($withVerificationResults) {
            $paymentAdditionalInfo['verificationResults'] = '{"authenticationMethodType":"THREE_DS","authorizationCode":"A33244","avsFullResult":"Y","avsNameResult":"M","cvvResult":"M","processorResponseCode":"D23","processorResponseText":"Stolen card"}';
        }

        foreach ((array) $order['paymentInfo']['payments'] as &$payment) {
            $paymentId = !empty($payment['obj']['id']) ? $payment['obj']['id'] : $payment['id'];
            $payment = UtilsHelper::toArrayRecursive(CommercetoolsPaymentsService::getById($paymentId));
            $updateActions = [
                [
                    "action" => "setCustomType",
                    "type" => [
                        "key" => 'paymentAdditionalInfo',
                        "typeId" => "type",
                    ],
                    "fields" => $paymentAdditionalInfo,
                ]
            ];
            if (empty($payment['paymentMethodInfo']['method'])) {
                $updateActions[] = [
                    "action" => "setMethodInfoMethod",
                    "method" => "{$paymentMethod}",
                ];
            }
            /*foreach ($paymentAdditionalInfo as $fiendName => $value) {
                $updateActions[] = [
                    "action" => "setCustomField",
                    "name" => "{$fiendName}",
                    "value" => "{$value}",
                ];
            }*/

            $payment = CommercetoolsPaymentsService::updateById($payment['id'], $updateActions, $payment['version']);

            if ($withVerificationResults && empty($payment->getTransactions())) {
                $payment = CommercetoolsPaymentsService::addPaymentTransaction($payment->getId(), [
                    "timestamp" => now()->toIso8601String(),
                    "type" => "Charge",
                    "amount" => [
                        "currencyCode" => $currencyCode,
                        "centAmount" => $totalCentAmount
                    ],
                    "state" => "Success",
                    "custom" => [
                        "type" => [
                            "key" => "paymentAdditionalInfo",
                            "typeId" => "type",
                        ],
                        "fields" => $paymentAdditionalInfo,
                    ],
                ], $payment->getVersion());
            }

            $payment = UtilsHelper::toArrayRecursive($payment);
            if (empty($payment['paymentMethodInfo']['paymentInterface'])) {
                $payment['paymentMethodInfo']['paymentInterface'] = $paymentInterface;
            }
        }

        return $returnForterOrder ? ForterOrder::getInstance($order) : $order;
    }
}
