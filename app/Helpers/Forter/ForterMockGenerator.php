<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Helpers\Forter;

class ForterMockGenerator
{
    protected static $loadedMockTemplates = [];

    protected static function getMockTemplate($templateName)
    {
        if (!isset(self::$loadedMockTemplates[$templateName])) {
            $template = file_get_contents(__DIR__ . '/forter_mock_schemas/' . $templateName  . '.json');
            self::$loadedMockTemplates[$templateName] = json_decode($template, true);
        }
        return self::$loadedMockTemplates[$templateName];
    }

    protected static function getForterTesterEmailsTemplate($forterDecision, $withCart = true)
    {
        $forterDecision = \preg_replace('/\s+/', '', $forterDecision);
        $firstName = ucfirst($forterDecision);

        $template = [
            "customerEmail" => "{$forterDecision}@forter.com",
            "shippingAddress" => [
                "firstName" => "{$firstName}",
                "lastName" => "Forter",
                "email" => "{$forterDecision}@forter.com",
            ],
            "billingAddress" => [
                "firstName" => "{$firstName}",
                "lastName" => "Forter",
                "email" => "{$forterDecision}@forter.com",
            ],
        ];

        if ($withCart) {
            $template['cart'] = [
                "obj" => $template,
            ];
        }

        return $template;
    }

    public static function addForterTesterEmailsToOrderMock($orderMock, $forterDecision)
    {
        $orderMock = \array_replace_recursive(
            $orderMock,
            self::getForterTesterEmailsTemplate($forterDecision)
        );

        return $orderMock;
    }

    public static function getUniqueUnitTestOrderId()
    {
        return \substr("forter-CT-app-unit-test-" . \uniqid(), 0, 40);
    }

    public static function addUnitTestIdToOrderMock($orderMock)
    {
        $orderMock = \array_replace_recursive(
            $orderMock,
            ['id' => self::getUniqueUnitTestOrderId()]
        );

        return $orderMock;
    }

    public static function getOrderMock($withPaymentMethod = false, $withVerificationResults = false, $withTransaction = false)
    {
        $orderMock = \array_replace_recursive(
            self::getMockTemplate('order'),
            self::getMockTemplate('order/cart'),
            self::getMockTemplate('order/custom_fields/initial'),
            ['id' => self::getUniqueUnitTestOrderId()]
        );

        if ($withPaymentMethod) {
            $orderMock = self::addPaymentToOrderMock($orderMock, $withPaymentMethod, $withVerificationResults, $withTransaction);
        }

        return $orderMock;
    }

    public static function addPaymentToOrderMock($orderMock, $paymentMethod, $withVerificationResults = false, $withTransaction = false)
    {
        $orderMock = \array_replace_recursive(
            $orderMock,
            self::getMockTemplate('order/payments'),
            self::getMockTemplate('order/payments/payment_method_info/' . $paymentMethod),
            self::getMockTemplate('order/payments/custom_fields/' . $paymentMethod),
        );

        if (!$withVerificationResults) {
            unset($orderMock['paymentInfo']['payments'][0]['obj']['custom']['fields']['verificationResults']);
        }

        if ($withTransaction) {
            $orderMock = self::addPaymentTransactionToOrderMock($orderMock, $paymentMethod, $withVerificationResults);
        }

        return $orderMock;
    }

    public static function addPaymentTransactionToOrderMock($orderMock, $paymentMethod, $withVerificationResults = false)
    {
        $orderMock = \array_replace_recursive(
            $orderMock,
            self::getMockTemplate('order/payments/transactions'),
            self::getMockTemplate('order/payments/transactions/custom_fields/' . $paymentMethod),
        );

        if (!$withVerificationResults) {
            unset($orderMock['paymentInfo']['payments'][0]['obj']['transactions'][0]['custom']['fields']['verificationResults']);
        }

        return $orderMock;
    }

    public static function getPaymentMock($paymentMethod, $withVerificationResults = false, $withTransaction = false)
    {
        $orderMock = self::getOrderMock($paymentMethod, $withVerificationResults, $withTransaction);
        return $orderMock['paymentInfo']['payments'][0]['obj'];
    }

    //========================================================================//

    public static function wrapOrderMockAsApiExtension($orderMock, $action = 'Create')
    {
        return \array_replace_recursive(
            self::getMockTemplate('wrappers/order_create_api_extension'),
            [
                'action' => "{$action}",
                'resource' => [
                    'typeId' => 'order',
                    'id' => $orderMock['id'],
                    'obj' => $orderMock,
                ]
            ]
        );
    }

    public static function wrapOrderMockAsSubscriptionMessage($orderMock, $type = 'OrderCreated')
    {
        return \array_replace_recursive(
            self::getMockTemplate('wrappers/order_created_subscription_message'),
            [
                'type' => "{$type}",
                'resource' => [
                    'typeId' => 'order',
                    'id' => $orderMock['id'],
                ],
                'order' => $orderMock,
            ]
        );
    }

    //========================================================================//

    public static function getDecisionReadyPaymentMethodOrderMock($paymentMethod, $forterDecision, $withVerificationResults = false, $withTransaction = false)
    {
        $orderMock = \array_replace_recursive(
            self::getOrderMock($paymentMethod, $withVerificationResults, $withTransaction),
            self::getForterTesterEmailsTemplate($forterDecision)
        );

        return $orderMock;
    }

    public static function getDecisionReadyCreditCardOrderMock($forterDecision, $withVerificationResults = false, $withTransaction = false)
    {
        $orderMock = \array_replace_recursive(
            self::getOrderMock('credit_card', $withVerificationResults, $withTransaction),
            self::getForterTesterEmailsTemplate($forterDecision)
        );

        return $orderMock;
    }

    public static function getDecisionReadyPayPalOrderMock($forterDecision, $withVerificationResults = false, $withTransaction = false)
    {
        $orderMock = \array_replace_recursive(
            self::getOrderMock('paypal', $withVerificationResults, $withTransaction),
            self::getForterTesterEmailsTemplate($forterDecision)
        );

        return $orderMock;
    }

    public static function getDecisionReadyApplePayOrderMock($forterDecision, $withVerificationResults = false, $withTransaction = false)
    {
        $orderMock = \array_replace_recursive(
            self::getOrderMock('applepay', $withVerificationResults, $withTransaction),
            self::getForterTesterEmailsTemplate($forterDecision)
        );

        return $orderMock;
    }
}
