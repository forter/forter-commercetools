<?php

namespace App\Helpers\Forter\Mocks;

class MockDataGenerator
{
    protected static $loadedMockTemplates = [];

    protected static function getMockTemplate($templateName)
    {
        if (!isset(self::$loadedMockTemplates[$templateName])) {
            $template = file_get_contents(__DIR__ . '/schema/' . $templateName  . '.json');
            self::$loadedMockTemplates[$templateName] = json_decode($template, true);
        }
        return self::$loadedMockTemplates[$templateName];
    }

    protected static function getForterTesterEmailsTemplate($decision, $withCart = true)
    {
        $decision = \preg_replace('/\s+/', '', $decision);
        $template = [
            "customerEmail" => "{$decision}@forter.com",
            "shippingAddress" => [
                "email" => "{$decision}@forter.com",
            ],
            "billingAddress" => [
                "email" => "{$decision}@forter.com",
            ],
        ];

        if ($withCart) {
            $template['cart'] = [
                "obj" => [
                    "customerEmail" => "{$decision}@forter.com",
                    "shippingAddress" => [
                        "email" => "{$decision}@forter.com",
                    ],
                    "billingAddress" => [
                        "email" => "{$decision}@forter.com",
                    ]
                ]
            ];
        }

        return $template;
    }

    public static function getOrderWithCcPaymentMock($decision, $withTransactionAndVerificationResults = false)
    {
        $orderMock = \array_replace_recursive(
            self::getMockTemplate('order_payload_lite'),
            self::getMockTemplate('order_cart'),
            self::getMockTemplate('order_payment_cc_no_transaction'),
            self::getMockTemplate('order_custom_fields_initial'),
            self::getForterTesterEmailsTemplate($decision)
        );

        if ($withTransactionAndVerificationResults) {
            $orderMock = self::addCcPaymentTransactionAndVerificationResultsToOrder($orderMock);
        }

        return $orderMock;
    }

    public static function addCcPaymentTransactionAndVerificationResultsToOrder($orderMock)
    {
        $orderMock = \array_replace_recursive(
            $orderMock,
            self::getMockTemplate('order_payment_cc_transaction_and_verification_results'),
        );

        return $orderMock;
    }

    public static function wrapOrderMockAsApiExtension($orderMock, $action = 'Create')
    {
        return \array_replace_recursive(
            self::getMockTemplate('order_create_api_extension_wrapper_lite'),
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
            self::getMockTemplate('order_created_subscription_message_wrapper_lite'),
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
}
