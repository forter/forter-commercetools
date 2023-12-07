<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace Tests\Feature\Forter;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Tests\Feature\Forter\AbstractForterTest;
use App\Helpers\Forter\ForterMockGenerator;
use App\Models\Forter\ForterOrder;
use App\Services\Forter\SchemaBuilders\ForterSchemaBuilder;

class ForterSchemaMapping extends AbstractForterTest
{
    public const CONFIG = [
        'forter_schema_mapping' => [
            'order' => [
                'connectionInformation' => [
                    'customerIP' => 'customerIP',
                    'forterMobileUID' => 'forterMobileUID',
                    'forterTokenCookie' => 'forterToken',
                    'merchantDeviceIdentifier' => 'merchantDeviceIdentifier',
                    'userAgent' => 'customerUserAgent',
                ],
            ],
            'payment_method_schema_type' => [
                'credit_card' => 'creditCard',
                'paypal' => 'paypal',
                'applepay' => 'applePay',
            ],
            'payment' => [
                'creditCard' => [
                    "bin" => "bin",
                    "cardBank" => "cardBank",
                    "cardBrand" => "cardBrand",
                    "cardType" => "cardType",
                    "countryOfIssuance" => "countryOfIssuance",
                    "expirationMonth" => "expirationMonth",
                    "expirationYear" => "expirationYear",
                    "nameOnCard" => "nameOnCard",
                    "lastFourDigits" => "lastFourDigits",
                    "fingerprint" => "fingerprint",
                    "fullResponsePayload" => "customFieldNameForFullResponsePayload", // Check custom mapping
                    "paymentProcessorData" => "paymentProcessorData",
                    "verificationResults" => "verificationResults",

                    'stripe' => [
                        "bin" => "cardBin", //Check custom mapping
                    ]
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
                    "fullPaypalResponsePayload" => "customFieldNameForFullPaypalResponsePayload", // Check custom mapping
                    "protectionEligibility" => "protectionEligibility",
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
                    "fullResponsePayload" => "customFieldNameForFullResponsePayload", // Check custom mapping
                    "paymentProcessorData" => "paymentProcessorData",
                    "verificationResults" => "verificationResults",
                ],
            ],
        ]
    ];

    /**
     * @method test_schema_mapping_credit_card_pre_approve
     */
    public function test_schema_mapping_credit_card_pre_approve(): void
    {
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock('credit_card', 'approve', true, true);
        $order = ForterOrder::getInstance($orderMock);
        $orderSchema = ForterSchemaBuilder::buildOrderSchema($order, 'pre');
        $this->assertCreditCardOrderSchema($orderSchema, 'pre', 'credit_card', 'approve');
    }

    /**
     * @method test_schema_mapping_credit_card_post_decline
     */
    public function test_schema_mapping_credit_card_post_decline(): void
    {
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock('credit_card', 'decline', true, true);
        $order = ForterOrder::getInstance($orderMock);
        $orderSchema = ForterSchemaBuilder::buildOrderSchema($order, 'post');
        $this->assertCreditCardOrderSchema($orderSchema, 'post', 'credit_card', 'decline');
    }

    /**
     * @method test_schema_mapping_paypal_pre_approve
     */
    public function test_schema_mapping_paypal_pre_approve(): void
    {
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock('paypal', 'approve', true, true);
        $order = ForterOrder::getInstance($orderMock);
        $orderSchema = ForterSchemaBuilder::buildOrderSchema($order, 'pre');
        $this->assertCreditCardOrderSchema($orderSchema, 'pre', 'paypal', 'approve');
    }

    /**
     * @method test_schema_mapping_paypal_post_decline
     */
    public function test_schema_mapping_paypal_post_decline(): void
    {
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock('paypal', 'decline', true, true);
        $order = ForterOrder::getInstance($orderMock);
        $orderSchema = ForterSchemaBuilder::buildOrderSchema($order, 'post');
        $this->assertCreditCardOrderSchema($orderSchema, 'post', 'paypal', 'decline');
    }

    /**
     * @method test_schema_mapping_applepay_pre_approve
     */
    public function test_schema_mapping_applepay_pre_approve(): void
    {
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock('applepay', 'approve', true, true);
        $order = ForterOrder::getInstance($orderMock);
        $orderSchema = ForterSchemaBuilder::buildOrderSchema($order, 'pre');
        $this->assertCreditCardOrderSchema($orderSchema, 'pre', 'applepay', 'approve');
    }

    /**
     * @method test_schema_mapping_applepay_post_decline
     */
    public function test_schema_mapping_applepay_post_decline(): void
    {
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock('applepay', 'decline', true, true);
        $order = ForterOrder::getInstance($orderMock);
        $orderSchema = ForterSchemaBuilder::buildOrderSchema($order, 'post');
        $this->assertCreditCardOrderSchema($orderSchema, 'post', 'applepay', 'decline');
    }

    /**
     * @method assertCreditCardOrderSchema
     * @param  array                             $orderSchema
     * @param  string                            $authStep
     * @param  string                            $paymentMethod
     * @param  string                            $forterDecision
     * @return $this
     */
    protected function assertCreditCardOrderSchema($orderSchema, $authStep, $paymentMethod, $forterDecision)
    {
        $this->assertIsArray($orderSchema);

        AssertableJson::fromArray($orderSchema)
            ->has('orderId')
            ->has('timeSentToForter')
            ->has('checkoutTime')
            ->where('authorizationStep', strtoupper($authStep) . "_AUTHORIZATION")
            ->has('connectionInformation', function (AssertableJson $json) {
                $json->where('customerIP', '127.0.0.1')
                    ->where('forterTokenCookie', '02b78690cd7e43afbdeb9f3bf219eb2c_1699814785233__UDF43-m4_15ck_tt')
                    ->where('userAgent', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36')
                    ->etc();
            })
            ->has('totalAmount', function (AssertableJson $json) {
                $json->where('amountLocalCurrency', '82.47')
                    ->where('currency', 'USD')
                    ->etc();
            })
            ->has('cartItems.0', function (AssertableJson $json) {
                $json
                    ->has('basicItemData.price.amountLocalCurrency')
                    ->has('basicItemData.price.currency')
                    ->has('basicItemData.productId')
                    ->has('basicItemData.name')
                    ->has('basicItemData.type')
                    ->has('basicItemData.quantity')
                    ->etc();
            })
            ->has('payment.0', function (AssertableJson $json) use ($paymentMethod, $forterDecision) {
                $json
                    ->has('amount', function (AssertableJson $json) {
                        $json->where('amountLocalCurrency', '82.47')
                            ->where('currency', 'USD')
                            ->etc();
                    })
                    ->has('billingDetails', function (AssertableJson $json) use ($forterDecision) {
                        $json
                            ->where('address.address1', 'Montgomery')
                            ->where('address.address2', '')
                            ->where('address.city', 'San Francisco')
                            ->where('address.country', 'US')
                            ->where('address.region', 'CA')
                            ->where('address.zip', '94104')
                            ->where('personalDetails.firstName', ucfirst($forterDecision))
                            ->where('personalDetails.lastName', 'Forter')
                            ->where('personalDetails.fullName', ucfirst($forterDecision) . ' Forter')
                            ->where('personalDetails.email', $forterDecision . '@forter.com')
                            ->where('phone.0.phone', '15557654321')
                            ->etc();
                    });
                switch ($paymentMethod) {
                    case 'credit_card':
                        $json
                            ->where('paymentMethodNickname', 'stripe')
                            ->has('creditCard', function (AssertableJson $json) use ($forterDecision) {
                                $json
                                    ->where('bin', '411111')
                                    ->where('cardBank', 'Chase')
                                    ->where('cardBrand', 'VISA')
                                    ->where('cardType', 'CREDIT')
                                    ->where('countryOfIssuance', 'US')
                                    ->where('expirationMonth', '03')
                                    ->where('expirationYear', '2030')
                                    ->where('nameOnCard', 'Tester Forter')
                                    ->where('lastFourDigits', '1142')
                                    ->where('fingerprint', 'Xt5EWLLDS7FJjR1c')
                                    ->where('paymentProcessorData.processorMerchantId', 'ncxwe5490asjdf') //It's enough to assert the first key of a json encoded field
                                    ->where('verificationResults.authenticationMethodType', 'THREE_DS')   //It's enough to assert the first key of a json encoded field
                                    ->where('fullResponsePayload.CCC', 'DDD')                             //It's enough to assert the first key of a json encoded field
                                    ->etc();
                            });
                        break;
                    case 'paypal':
                        $json
                            ->where('paymentMethodNickname', 'paypal')
                            ->has('paypal', function (AssertableJson $json) use ($forterDecision) {
                                $json
                                    ->where('authorizationId', '2WC75407LV7300439')
                                    ->where('payerAccountCountry', 'US')
                                    ->where('payerAddressStatus', 'CONFIRMED')
                                    ->where('payerEmail', 'tester@forter.com')
                                    ->where('payerId', 'FD000A45P')
                                    ->where('payerStatus', 'VERIFIED')
                                    ->where('paymentGatewayData.gatewayName', 'braintree')
                                    ->where('paymentGatewayData.gatewayTransactionId', 'fjdsS46sdklFd20')
                                    ->where('paymentId', 'PAY-0MB00000KR8073311KZSDEYA')
                                    ->where('paymentStatus', 'COMPLETED')
                                    ->where('protectionEligibility', 'ELIGIBLE')
                                    ->where('fullPaypalResponsePayload.AAA', 'BBB') //It's enough to assert the first key of a json encoded field
                                    ->etc();
                            });
                        break;
                    case 'applepay':
                        $json
                            ->where('paymentMethodNickname', 'applepay')
                            ->has('applePay', function (AssertableJson $json) use ($forterDecision) {
                                $json
                                    ->where('cardBank', 'Chase')
                                    ->where('cardBrand', 'VISA')
                                    ->where('cardType', 'CREDIT')
                                    ->where('countryOfIssuance', 'US')
                                    ->where('creationTime', 1430997968)
                                    ->where('expirationMonth', '03')
                                    ->where('expirationYear', '2018')
                                    ->where('lastFourDigits', '4242')
                                    ->where('nameOnCard', 'Tester Forter')
                                    ->where('token', 'tkn-77620C360132856A103477D2959967AB')
                                    ->where('paymentProcessorData.processorMerchantId', 'ncxwe5490asjdf') //It's enough to assert the first key of a json encoded field
                                    ->where('verificationResults.authenticationMethodType', 'THREE_DS')   //It's enough to assert the first key of a json encoded field
                                    ->where('fullResponsePayload.EEE', 'FFF')                            //It's enough to assert the first key of a json encoded field
                                    ->etc();
                            });
                        break;
                    default:
                        throw new \Exception("This test has no expected schema for given payment method: " . $paymentMethod, 1);
                        break;
                }
            })
            ->has('primaryDeliveryDetails', function (AssertableJson $json) {
                $json
                    ->where('deliveryMethod', 'Standard Delivery')
                    ->where('deliveryPrice.amountLocalCurrency', '100')
                    ->where('deliveryPrice.currency', 'USD')
                    ->where('deliveryType', 'PHYSICAL')
                    ->etc();
            })
            ->has('primaryRecipient', function (AssertableJson $json) use ($forterDecision) {
                $json
                    ->where('address.address1', 'Montgomery')
                    ->where('address.address2', '')
                    ->where('address.city', 'San Francisco')
                    ->where('address.country', 'US')
                    ->where('address.region', 'CA')
                    ->where('address.zip', '83003')
                    ->where('personalDetails.firstName', ucfirst($forterDecision))
                    ->where('personalDetails.lastName', 'Forter')
                    ->where('personalDetails.fullName', ucfirst($forterDecision) . ' Forter')
                    ->where('personalDetails.email', $forterDecision . '@forter.com')
                    ->where('phone.0.phone', '15598754654')
                    ->etc();
            });

        return $this;
    }
}
