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

use Illuminate\Support\Carbon;
use App\Services\Forter\SchemaBuilders\ForterPaymentMethodSchemaBuilder;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterPayment;
use App\Helpers\UtilsHelper;

class ForterSchemaBuilder
{
    /**
     * Build order schema for order validation request (v2/orders/{orderId})
     * @method buildOrderSchema
     * @param  ForterOrder     $order
     * @param  string          $authorizationStep  ('PRE' / 'POST')
     * @return array
     */
    public static function buildOrderSchema(ForterOrder $order, $authorizationStep)
    {
        return [
            'orderId' => $order->getForterOrderId(),
            'orderType' => 'WEB',
            'authorizationStep' => strtoupper($authorizationStep) . '_AUTHORIZATION',
            'timeSentToForter' => Carbon::now()->getTimestampMs(),
            'checkoutTime' => $order->getCreatedAtTimestampMs(),
            'connectionInformation' => self::buildOrderConnectionInformationSchema($order),
            'totalAmount' => self::buildOrderTotalAmountSchema($order),
            'cartItems' => self::buildOrderCartItemsSchema($order),
            'payment' => self::buildOrderPaymentSchema($order),
            'primaryDeliveryDetails' => self::buildOrderPrimaryDeliveryDetailsSchema($order),
            'primaryRecipient' => self::buildOrderPrimaryRecipientSchema($order),
        ];
    }

    /**
     * Build order status schema for order status request (v2/status/{orderId})
     * @param  ForterOrder            $order
     * @return array
     */
    public static function buildOrderStatusSchema(ForterOrder $order)
    {
        foreach ($order->getPayments() as $payment) {
            $verificationResults = ForterPaymentMethodSchemaBuilder::buildPaymentVerificationResults($payment);
            break;
        }
        return [
            "orderId" => $order->getForterOrderId(),
            "eventTime" => Carbon::now()->getTimestampMs(),
            "updatedStatus" => $order->getForterOrderStatusByOrderState(),
            "verificationResults" => !empty($verificationResults) ? $verificationResults : null,
        ];
    }

    //========================================================================//

    public static function buildOrderConnectionInformationSchema(ForterOrder $order)
    {
        return [
            'customerIP' => $order->getForterSchemaOrderField('connectionInformation.customerIP', '127.0.0.1'),
            'forterMobileUID' => $order->getForterSchemaOrderField('connectionInformation.forterMobileUID', null),
            'forterTokenCookie' => $order->getForterSchemaOrderField('connectionInformation.forterTokenCookie', null),
            'merchantDeviceIdentifier' => $order->getForterSchemaOrderField('connectionInformation.merchantDeviceIdentifier', null),
            'userAgent' => $order->getForterSchemaOrderField('connectionInformation.userAgent', ''),
        ];
    }

    public static function buildOrderTotalAmountSchema(ForterOrder $order)
    {
        return [
            'amountLocalCurrency' => (string) UtilsHelper::addFractionDigitsToNumber($order->getTaxedPriceTotalGross('centAmount'), $order->getTaxedPriceTotalGross('fractionDigits')),
            'currency' => $order->getTaxedPriceTotalGross('currencyCode'),
        ];
    }

    public static function buildOrderCartItemsSchema(ForterOrder $order)
    {
        $schema = [];

        foreach ($order->getLineItems() as $lineItem) {
            $name = array_values((array)$lineItem['name']);
            $schema[] = [
                'basicItemData' => [
                    'price' => [
                        'amountLocalCurrency' => (string) UtilsHelper::addFractionDigitsToNumber($lineItem['price']['value']['centAmount'], $lineItem['price']['value']['fractionDigits']),
                        'currency' => $lineItem['price']['value']['currencyCode']
                    ],
                    'productId' => $lineItem['productId'],
                    'name' => (!empty($lineItem['name']['locale'])) ? $lineItem['name']['locale'] : array_shift($name),
                    'type' => 'TANGIBLE', // No such info. Hard-coded since it's required.
                    'quantity' => $lineItem['quantity'],
                ],
            ];
        }
        return $schema;
    }

    public static function buildOrderPaymentSchema(ForterOrder $order)
    {
        $schema = [];

        foreach ($order->getPayments() as $payment) {
            $paymentSchema = [
                'amount' => [
                    'amountLocalCurrency' => (string) UtilsHelper::addFractionDigitsToNumber($payment->getAmountPlanned('centAmount'), $payment->getAmountPlanned('fractionDigits')),
                    'currency' => $payment->getAmountPlanned('currencyCode'),
                ],
                'billingDetails' => self::buildOrderBillingDetailsSchema($order),
                'paymentMethodNickname' => $payment->getPaymentInterface(),
            ];

            switch ($payment->getForterPaymentSchemaType()) {
                case 'creditCard':
                    $paymentSchema['creditCard'] = ForterPaymentMethodSchemaBuilder::buildCreditCardSchema($payment);
                    break;

                case 'paypal':
                    $paymentSchema['paypal'] = ForterPaymentMethodSchemaBuilder::buildPaypalSchema($payment);
                    break;

                case 'applePay':
                    $paymentSchema['applePay'] = ForterPaymentMethodSchemaBuilder::buildApplePaySchema($payment);
                    break;

                case 'androidPay':
                    $paymentSchema['androidPay'] = ForterPaymentMethodSchemaBuilder::buildAndroidPaySchema($payment);
                    break;

                case 'installmentService':
                    $paymentSchema['installmentService'] = ForterPaymentMethodSchemaBuilder::buildInstallmentServiceSchema($payment, $billingDetailsSchema);
                    break;
            }

            $schema[] = $paymentSchema;
            //break;
        }
        return $schema;
    }

    public static function buildOrderPrimaryDeliveryDetailsSchema(ForterOrder $order)
    {
        $shippingInfo = $order->getShippingInfo();
        $shippingMethodName = !empty($shippingInfo['shippingMethodName']) ? $shippingInfo['shippingMethodName'] : 'unknown';
        $centAmount = !empty($shippingInfo['price']['centAmount']) ? $shippingInfo['price']['centAmount'] : '000';
        $fractionDigits = !empty($shippingInfo['price']['fractionDigits']) ? $shippingInfo['price']['fractionDigits'] : 2;
        $currencyCode = !empty($shippingInfo['price']['currencyCode']) ? $shippingInfo['price']['currencyCode'] : (!empty($order['taxedPrice']['totalGross']['currencyCode']) ? $order['taxedPrice']['totalGross']['currencyCode'] : $order['totalPrice']['currencyCode']);
        return [
            'deliveryMethod' => $shippingMethodName, // Name of the shipping method
            'deliveryPrice' => [
                'amountLocalCurrency' => (string) UtilsHelper::addFractionDigitsToNumber($centAmount, $fractionDigits),
                'currency' => $currencyCode,
            ],
            'deliveryType' => 'PHYSICAL', // No such info. Hard-coded since it's required.
        ];
    }

    public static function buildAddressSchema(array $address)
    {
        return [
            'address1' => $address['address1'],
            'address2' => $address['address2'],
            'city' => $address['city'],
            'country' => $address['country'],
            'region' => $address['region'],
            'zip' => $address['zip'],
        ];
    }

    public static function buildPersonalDetailsSchema(array $details)
    {
        return [
            'firstName' => $details['firstName'],
            'lastName' => $details['lastName'],
            'fullName' => $details['fullName'],
            'email' => $details['email'],
        ];
    }

    public static function extractAddressFields(array $address)
    {
        return [
            'firstName' => $address['firstName'],
            'lastName' => $address['lastName'],
            'fullName' => implode(' ', [$address['firstName'], $address['lastName']]),
            'address1' => !empty($address['streetNumber']) ? $address['streetNumber'] . ' ' . $address['streetName'] : $address['streetName'],
            'address2' => $address['additionalStreetInfo'],
            'city' => $address['city'],
            'country' => $address['country'],
            'region' => $address['region'],
            'zip' => $address['postalCode'],
            'phone' => $address['phone'],
            'mobile' => $address['mobile'],
            'email' => $address['email'],
        ];
    }

    public static function buildOrderBillingDetailsSchema(ForterOrder $order)
    {
        $address = self::extractAddressFields($order->getBillingAddress());
        $address['email'] = $address['email'] ? $address['email'] : $order->getCustomerEmail();
        return [
            'address' => self::buildAddressSchema($address),
            'personalDetails' => self::buildPersonalDetailsSchema($address),
            'phone' => [
                [
                    'phone' => $address['phone'] ?: $address['mobile'],
                ],
            ],
        ];
    }

    public static function buildOrderPrimaryRecipientSchema(ForterOrder $order)
    {
        $address = array_replace(
            self::extractAddressFields($order->getBillingAddress()),
            self::extractAddressFields($order->getShippingAddress())
        );
        $address['email'] = $address['email'] ? $address['email'] : $order->getCustomerEmail();
        return [
            'address' => self::buildAddressSchema($address),
            'personalDetails' => self::buildPersonalDetailsSchema($address),
            'phone' => [
                [
                    'phone' => $address['phone'] ?: $address['mobile'],
                ],
            ],
        ];
    }
}
