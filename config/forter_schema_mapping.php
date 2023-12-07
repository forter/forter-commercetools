<?php
/**
 * Forter Commercetools app
 *
 * Map Forter schema fields to Commercetools fields.
 * =======================================================================
 * In order to override this file locally without modifying it,
 * Add a file named forter_schema_custom_mapping.php in the same dir,
 * Then, add your custom mappings to that file instead.
 * * Duplicate forter_schema_custom_mapping.php.example for a quick start.
 *
 * Mapping instructions: ../docs/forter-schema-custom-mapping-guide.md
 * =======================================================================
 */

$mapping = [

    /**
      * custom.{fieldName} or {fieldName} => look in the order's custom fields (default)
     */
    'order' => [
        //'orderId' => 'orderNumber', // Uncommect for using 'orderNumber' as orderId (instead of 'id')

        'connectionInformation' => [
            'customerIP' => 'customerIP',
            'forterMobileUID' => 'forterMobileUID',
            'forterTokenCookie' => 'forterToken',
            'merchantDeviceIdentifier' => 'merchantDeviceIdentifier',
            'userAgent' => 'customerUserAgent',
        ],
    ],

    'payment_method_schema_type' => [
        'cc' => 'creditCard',
        'credit_card' => 'creditCard',
        'visa' => 'creditCard',
        'paypal' => 'paypal',
        'applepay' => 'applePay',
        'paybright' => 'installmentService',
        /* Route any local/custom payment method (paymentMethodInfo[method]). Replace spaces with underscores */
    ],

    /**
      * payment.{fieldName} => look in the payment root object
      * paymentMethodInfo.{fieldName} => look in the payment's paymentMethodInfo object
      * custom.{fieldName} or {fieldName} => look in the payment's custom fields (default)
     */
    'payment' => [
        'creditCard' => [
            // Defaults:
            "bin" => "cardBin",
            "cardBank" => "cardBank",
            "cardBrand" => "cardBrand",
            "cardType" => "cardType",
            "countryOfIssuance" => "countryOfIssuance",
            "expirationMonth" => "expirationMonth",
            "expirationYear" => "expirationYear",
            "nameOnCard" => "nameOnCard",
            "lastFourDigits" => "lastFourDigits",
            "fingerprint" => "fingerprint",
            "fullResponsePayload" => "fullResponsePayload",
            "paymentProcessorData" => "paymentProcessorData",
            "verificationResults" => "verificationResults",

            // PaymentInterface specific:
            'stripe' => [
                // "bin" => "cardBin",
                // "cardBank" => "cardBank",
                // ...
            ],

            // PaymentInterface specific:
            'adyen' => [
                // "bin" => "cardBin",
                // "cardBank" => "cardBank",
                // ...
            ],
        ],

        'paypal' => [
            "authorizationId" => "authorizationId",
            "payerAccountCountry" => "payerAccountCountry",
            "payerAddressStatus" => "payerAddressStatus",
            "payerEmail" => "payerEmail",
            "payerId" => "payerId",
            "payerStatus" => "payerStatus",
            "paymentGatewayData" => [
                "gatewayName" => "paymentMethodInfo.method",
                "gatewayTransactionId" => "gatewayTransactionId",
            ],
            "paymentId" => "paymentId",
            "paymentStatus" => "paymentStatus",
            "fullPaypalResponsePayload" => "fullPaypalResponsePayload",
            "protectionEligibility" => "protectionEligibility",

            // PaymentInterface specific:
            'adyen' => [
                // "authorizationId" => "authorizationId",
                // "payerAccountCountry" => "payerAccountCountry",
                // ...
            ],
        ],

        'applePay' => [
            "cardBank" => "cardBank",
            "cardBrand" => "cardBrand",
            "cardType" => "cardType",
            "countryOfIssuance" => "countryOfIssuance",
            "creationTime" => "creationTime",
            "expirationMonth" => "expirationMonth",
            "expirationYear" => "expirationYear",
            "nameOnCard" => "nameOnCard",
            "lastFourDigits" => "lastFourDigits",
            "fingerprint" => "fingerprint",
            "token" => "token",
            "fullResponsePayload" => "fullResponsePayload",
            "paymentProcessorData" => "paymentProcessorData",
            "verificationResults" => "verificationResults",

            // PaymentInterface specific:
            // ...
        ],

        'androidPay' => [
            "bin" => "cardBin",
            "cardBank" => "cardBank",
            "cardBrand" => "cardBrand",
            "cardType" => "cardType",
            "countryOfIssuance" => "countryOfIssuance",
            "creationTime" => "creationTime",
            "expirationMonth" => "expirationMonth",
            "expirationYear" => "expirationYear",
            "nameOnCard" => "nameOnCard",
            "lastFourDigits" => "lastFourDigits",
            "fingerprint" => "fingerprint",
            "token" => "token",
            "threeDSecure" => "threeDSecureData",
            "fullResponsePayload" => "fullResponsePayload",
            "paymentProcessorData" => "paymentProcessorData",
            "verificationResults" => "verificationResults",

            // PaymentInterface specific:
            // ...
        ],

        'installmentService' => [
            "serviceName" => 'cardType',
            "serviceResponseCode" => 'serviceResponseCode',
            "paymentId" => 'transactionId',
            "fullResponsePayload" => 'fullResponsePayload'

            // PaymentInterface specific:
            // ...
        ]
    ]
];

// Override with custom mapping if exists
if (file_exists(__DIR__ . '/forter_schema_custom_mapping.php')) {
    $customMapping = require(__DIR__ . '/forter_schema_custom_mapping.php');
    return array_replace_recursive(
        $mapping,
        $customMapping
    );
}

return $mapping;
