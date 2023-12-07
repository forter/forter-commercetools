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

class ForterOrderStatusSchemaAndRequest extends AbstractForterTest
{
    public const CONFIG = [
        'forter_schema_mapping' => [
            'payment' => [
                'creditCard' => [
                    "verificationResults" => "verificationResults",
                ],
            ],
        ]
    ];

    /**
     * @method test_order_status_schema_and_request_credit_card
     */
    public function test_order_status_schema_and_request_credit_card(): void
    {
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock('credit_card', 'approve', true, true);
        $order = ForterOrder::getInstance($orderMock);
        $orderSchema = ForterSchemaBuilder::buildOrderStatusSchema($order);
        $this->assertCreditCardOrderStatusSchema($orderSchema, 'credit_card', 'approve');
    }

    /**
     * @method assertCreditCardOrderStatusSchema
     * @param  array                             $orderSchema
     * @param  string                            $paymentMethod
     * @param  string                            $forterDecision
     * @return $this
     */
    protected function assertCreditCardOrderStatusSchema($orderSchema, $paymentMethod, $forterDecision)
    {
        $this->assertIsArray($orderSchema);

        AssertableJson::fromArray($orderSchema)
            ->has('orderId')
            ->has('eventTime')
            ->where('updatedStatus', 'PROCESSING')
            ->has('verificationResults', function (AssertableJson $json) {
                $json
                    ->where('authenticationMethodType', 'THREE_DS')
                    ->where('authorizationCode', 'A33244')
                    ->where('avsFullResult', 'Y')
                    ->where('avsNameResult', 'M')
                    ->where('cvvResult', 'M')
                    ->where('cvvResult', 'M')
                    ->where('processorResponseText', 'Stolen card')
                    ->etc();
            });

        return $this;
    }
}
