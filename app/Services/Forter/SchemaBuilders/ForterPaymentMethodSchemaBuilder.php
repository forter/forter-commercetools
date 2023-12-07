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

use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterPayment;

class ForterPaymentMethodSchemaBuilder
{
    /**
     * Return filtered array, try to decode from json if value is string.
     * @method maybeJsonDecodeAndFilter
     * @param  mixed                   $value
     * @return array|null
     */
    public static function maybeJsonDecodeAndFilter($value)
    {
        $value = is_string($value) ? (array) json_decode($value, true) : (array) $value;
        return array_filter($value) ?: null;
    }

    /**
     * @method buildPaymentVerificationResults
     * @param  ForterPayment     $payment
     * @return array
     */
    public static function buildPaymentVerificationResults(ForterPayment $payment)
    {
        return self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('verificationResults', '{}'));
    }

    /**
     * @method buildCreditCardSchema
     * @param  ForterPayment     $payment
     * @return array
     */
    public static function buildCreditCardSchema(ForterPayment $payment)
    {
        return array_filter([
            'bin' => $payment->getForterSchemaPaymentField('bin'),
            'cardBank' => $payment->getForterSchemaPaymentField('cardBank'),
            'cardBrand' => $payment->getForterSchemaPaymentField('cardBrand'),
            'cardType' => $payment->getForterSchemaPaymentField('cardType'),
            'countryOfIssuance' => $payment->getForterSchemaPaymentField('countryOfIssuance'),
            'expirationMonth' => $payment->getForterSchemaPaymentField('expirationMonth'),
            'expirationYear' => $payment->getForterSchemaPaymentField('expirationYear'),
            'nameOnCard' => $payment->getForterSchemaPaymentField('nameOnCard'),
            'lastFourDigits' => $payment->getForterSchemaPaymentField('lastFourDigits'),
            'fingerprint' => $payment->getForterSchemaPaymentField('fingerprint'),
            'fullResponsePayload' => self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('fullResponsePayload', '{}')),
            'paymentProcessorData' => self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('paymentProcessorData', '{}')),
            'verificationResults' => self::buildPaymentVerificationResults($payment),
        ]);
    }

    /**
     * @method buildPaypalSchema
     * @param  ForterPayment     $payment
     * @return array
     */
    public static function buildPaypalSchema(ForterPayment $payment)
    {
        return array_filter([
            'authorizationId' => $payment->getForterSchemaPaymentField('authorizationId'),
            'payerAccountCountry' => $payment->getForterSchemaPaymentField('payerAccountCountry'),
            'payerAddressStatus' => $payment->getForterSchemaPaymentField('payerAddressStatus'),
            'payerEmail' => $payment->getForterSchemaPaymentField('payerEmail'),
            'payerId' => $payment->getForterSchemaPaymentField('payerId'),
            'payerStatus' => $payment->getForterSchemaPaymentField('payerStatus'),
            'paymentGatewayData' => array_filter([
                "gatewayName" => $payment->getForterSchemaPaymentField('paymentGatewayData.gatewayName'),
                "gatewayTransactionId" => $payment->getForterSchemaPaymentField('paymentGatewayData.gatewayTransactionId'),
            ]),
            'paymentId' => $payment->getForterSchemaPaymentField('paymentId'),
            'paymentStatus' => $payment->getForterSchemaPaymentField('paymentStatus'),
            'protectionEligibility' => $payment->getForterSchemaPaymentField('protectionEligibility'),
            'fullPaypalResponsePayload' => self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('fullPaypalResponsePayload', '{}')),

        ]);
    }

    /**
     * @method buildApplePaySchema
     * @param  ForterPayment     $payment
     * @return array
     */
    public static function buildApplePaySchema(ForterPayment $payment)
    {
        return array_filter([
            'cardBank' => $payment->getForterSchemaPaymentField('cardBank'),
            'cardBrand' => $payment->getForterSchemaPaymentField('cardBrand'),
            'cardType' => $payment->getForterSchemaPaymentField('cardType'),
            'countryOfIssuance' => $payment->getForterSchemaPaymentField('countryOfIssuance'),
            'creationTime' => $payment->getForterSchemaPaymentField('creationTime'),
            'expirationMonth' => $payment->getForterSchemaPaymentField('expirationMonth'),
            'expirationYear' => $payment->getForterSchemaPaymentField('expirationYear'),
            'nameOnCard' => $payment->getForterSchemaPaymentField('nameOnCard'),
            'lastFourDigits' => $payment->getForterSchemaPaymentField('lastFourDigits'),
            'fingerprint' => $payment->getForterSchemaPaymentField('fingerprint'),
            'token' => $payment->getForterSchemaPaymentField('token'),
            'fullResponsePayload' => self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('fullResponsePayload', '{}')),
            'paymentProcessorData' => self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('paymentProcessorData', '{}')),
            'verificationResults' => self::buildPaymentVerificationResults($payment),
        ]);
    }

    /**
     * @method buildAndroidPaySchema
     * @param  ForterPayment     $payment
     * @return array
     */
    public static function buildAndroidPaySchema(ForterPayment $payment)
    {
        return array_filter([
            'bin' => $payment->getForterSchemaPaymentField('bin'),
            'cardBank' => $payment->getForterSchemaPaymentField('cardBank'),
            'cardBrand' => $payment->getForterSchemaPaymentField('cardBrand'),
            'cardType' => $payment->getForterSchemaPaymentField('cardType'),
            'countryOfIssuance' => $payment->getForterSchemaPaymentField('countryOfIssuance'),
            'creationTime' => $payment->getForterSchemaPaymentField('creationTime'),
            'expirationMonth' => $payment->getForterSchemaPaymentField('expirationMonth'),
            'expirationYear' => $payment->getForterSchemaPaymentField('expirationYear'),
            'nameOnCard' => $payment->getForterSchemaPaymentField('nameOnCard'),
            'lastFourDigits' => $payment->getForterSchemaPaymentField('lastFourDigits'),
            'fingerprint' => $payment->getForterSchemaPaymentField('fingerprint'),
            'token' => $payment->getForterSchemaPaymentField('token'),
            'threeDSecure' => $payment->getForterSchemaPaymentField('threeDSecureData'),
            'fullResponsePayload' => self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('fullResponsePayload', '{}')),
            'paymentProcessorData' => self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('paymentProcessorData', '{}')),
            'verificationResults' => self::buildPaymentVerificationResults($payment),
        ]);
    }

    /**
     * @method buildInstallmentServiceSchema
     * @param  ForterPayment     $payment
     * @return array
     */
    public static function buildInstallmentServiceSchema(ForterPayment $payment, ForterOrder $order)
    {
        return array_filter([
            "serviceName" => $payment->getForterSchemaPaymentField('serviceName'),
            "firstName" => $order->getShippingAddress()->getFirstname(),
            "lastName" => $order->getShippingAddress()->getLastname(),
            "serviceResponseCode" => $payment->getForterSchemaPaymentField('serviceResponseCode'),
            "paymentId" => $payment->getForterSchemaPaymentField('paymentId'),
            "fullResponsePayload" => self::maybeJsonDecodeAndFilter($payment->getForterSchemaPaymentField('fullResponsePayload', '{}')),
        ]);
    }
}
