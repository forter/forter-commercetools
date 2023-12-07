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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use App\Helpers\UtilsHelper;
use App\Helpers\Forter\ForterMockGenerator;
use App\Models\Forter\ForterCommercetoolsMessage;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterResponse;
use App\Services\Forter\Processors\OrderValidation\ForterOrderPostDecisionProcessor;
use App\Services\Forter\SchemaBuilders\ForterDecisionActionsBuilder;

class AbstractForterTest extends TestCase
{
    public const CONFIG = [];

    /**
     * Set config for the current runtime
     * Override the current app config with test required config
     * @method initConfig
     * @param  array   $config
     * @return $this
     */
    protected function initConfig($config = [])
    {
        config(!empty($config) ? $config : static::CONFIG);
        return $this;
    }

    /**
     * Set config for the API extension handler runtime
     * @method addTestConfigToPayload
     * @param  array                 $payload
     * @param  array                  $config
     */
    protected function addTestConfigToPayload($payload, $config = [])
    {
        $payload['_test'] = encrypt(\json_encode([
            'config' => !empty($config) ? $config : static::CONFIG,
        ]));
        return $payload;
    }

    protected function getApiExtensionsRequestHeaders()
    {
        return [
            'authorization' => 'Basic ' . UtilsHelper::getCommercetoolsApiExtensionsBasicAuthSecret(),
            'x-correlation-id' => 'forter-commercetools-app-test',
        ];
    }

    /**
     * @method makeCommercetoolsApiExtensionsRequest
     * @param  array                                $orderMock
     * @param  array                                $headers
     * @return TestResponse
     */
    protected function makeCommercetoolsApiExtensionsRequest(array $order, array $headers = null)
    {
        $payload = ForterMockGenerator::wrapOrderMockAsApiExtension($order);
        $payload = $this->addTestConfigToPayload($payload);
        return $this
            ->withHeaders(is_array($headers) ? $headers : $this->getApiExtensionsRequestHeaders())
            ->postJson('/commercetools/api/extensions', $payload);
    }

    //========================================================================//

    /**
     * Assert that a json/array has the expected CT errors list for a Forter decision and a BLOCK_ORDER_PLACE action
     * @method assertForterDecisionActionsBlockOrderPlace
     * @param  array                   $value
     * @return $this
     */
    protected function assertForterDecisionActionsBlockOrderPlace($value)
    {
        $this->assertIsArray($value);

        AssertableJson::fromArray($value)
            ->has('errors', 1)
            ->has('errors.0', function (AssertableJson $json) {
                $json
                    ->where('code', 'InvalidOperation')
                    ->whereType('message', 'string')
                    ->etc();
            });

        return $this;
    }

    /**
     * Assert that a json/array has the expected CT actions list for a Forter decision and a DO_NOTHING action
     * @method assertForterDecisionActionsDoNothing
     * @param  array                   $value
     * @param  string                  $authStep  'pre' / 'post'
     * @param  string                  $forterDecision
     * @return $this
     */
    protected function assertForterDecisionActionsDoNothing($value, $authStep, $forterDecision)
    {
        $this->assertIsArray($value);

        AssertableJson::fromArray($value)
            ->has('actions', 1)
            ->has('actions.0', function (AssertableJson $json) use ($forterDecision, $authStep) {
                $json
                    ->where('action', 'setCustomType')
                    ->has('type', function (AssertableJson $json) {
                        $json
                            ->where('key', ForterDecisionActionsBuilder::FORTER_CUSTOM_TYPE_KEY)
                            ->where('typeId', 'type');
                    })
                    ->has('fields', function (AssertableJson $json) use ($forterDecision, $authStep) {
                        $json->hasAll([
                            'forterDecision',
                            'forterResponse',
                            'forterReason',
                            'forterRecommendations',
                            'forterToken',
                            'customerIP',
                            'customerUserAgent',
                        ])
                        ->where('forterDecision', $forterDecision)
                        ->where('forterResponse', function (string $forterResponse) use ($authStep) {
                            return strpos($forterResponse, $authStep . '_auth_') !== false;
                        })
                        ->etc();
                    });
            });

        return $this;
    }

    /**
     * Assert that a json/array has the expected CT actions list for a Forter decision and a SET_ORDER_STATE action
     * @method assertForterDecisionActionsSetOrderState
     * @param  array                   $value
     * @param  string                  $authStep  'pre' / 'post'
     * @param  string                  $forterDecision
     * @param  string                  $orderState  Default: 'TestOrderState'
     * @return $this
     */
    protected function assertForterDecisionActionsSetOrderState($value, $authStep, $forterDecision, $orderState = 'TestOrderState')
    {
        $this->assertIsArray($value);

        AssertableJson::fromArray($value)
            ->has('actions', 2)
            ->has('actions.0', function (AssertableJson $json) use ($forterDecision, $authStep) {
                $json
                    ->where('action', 'setCustomType')
                    ->has('type', function (AssertableJson $json) {
                        $json
                            ->where('key', ForterDecisionActionsBuilder::FORTER_CUSTOM_TYPE_KEY)
                            ->where('typeId', 'type');
                    })
                    ->has('fields', function (AssertableJson $json) use ($forterDecision, $authStep) {
                        $json->hasAll([
                            'forterDecision',
                            'forterResponse',
                            'forterReason',
                            'forterRecommendations',
                            'forterToken',
                            'customerIP',
                            'customerUserAgent',
                        ])
                        ->where('forterDecision', $forterDecision)
                        ->where('forterResponse', function (string $forterResponse) use ($authStep) {
                            return strpos($forterResponse, $authStep . '_auth_') !== false;
                        })
                        ->etc();
                    });
            })
            ->has('actions.1', function (AssertableJson $json) use ($orderState) {
                $json
                    ->where('action', 'changeOrderState')
                    ->where('orderState', $orderState);
            });

        return $this;
    }

    /**
     * Assert that a TestResponse has the expected CT errors list for a Forter decision and a BLOCK_ORDER_PLACE action
     * @method assertForterDecisionActionsBlockOrderPlaceResponse
     * @param  array                   $value
     * @return $this
     */
    public function assertForterDecisionActionsBlockOrderPlaceResponse(TestResponse $response)
    {
        $response->assertStatus(400);
        $response->assertJsonCount(1);
        $this->assertForterDecisionActionsBlockOrderPlace($response->json());
        return $this;
    }

    /**
     * Assert that a TestResponse has the expected CT actions list for a Forter decision and a DO_NOTHING action
     * @method assertForterDecisionActionsDoNothingResponse
     * @param  array                   $value
     * @param  string                  $authStep  'pre' / 'post'
     * @param  string                  $forterDecision
     * @return $this
     */
    public function assertForterDecisionActionsDoNothingResponse(TestResponse $response, $authStep, $forterDecision)
    {
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $this->assertForterDecisionActionsDoNothing($response->json(), $authStep, $forterDecision);
        return $this;
    }

    /**
     * Assert that a TestResponse has the expected CT actions list for a Forter decision and a SET_ORDER_STATE action
     * @method assertForterDecisionActionsSetOrderStateResponse
     * @param  array                   $value
     * @param  string                  $authStep  'pre' / 'post'
     * @param  string                  $forterDecision
     * @param  string                  $orderState  Default: 'TestOrderState'
     * @return $this
     */
    public function assertForterDecisionActionsSetOrderStateResponse(TestResponse $response, $authStep, $forterDecision, $orderState = 'TestOrderState')
    {
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $this->assertForterDecisionActionsSetOrderState($response->json(), $authStep, $forterDecision, $orderState);
        return $this;
    }

    /**
     * @method assertOrderPostDecisionExpectedActions
     * @param  array                                  $orderMock
     * @param  string                                 $forterDecision
     * @return $this
     */
    public function assertOrderPostDecisionExpectedActions($orderMock, $forterDecision)
    {
        $payload = ForterMockGenerator::wrapOrderMockAsSubscriptionMessage($orderMock);

        $messageModel = ForterCommercetoolsMessage::getInstance($payload);
        $order = $messageModel->getOrderModel();

        $this->assertInstanceOf(ForterCommercetoolsMessage::class, $messageModel);
        $this->assertInstanceOf(ForterOrder::class, $order);

        $processor = new ForterOrderPostDecisionProcessor($messageModel);

        // Get Forter post decision.
        $forterResponse = $processor->getOrderPostDecision($order);

        $this->assertInstanceOf(ForterResponse::class, $forterResponse);
        $this->assertSame($forterResponse->getStatus(), 'success');
        $this->assertSame($forterResponse->getDecision(), $forterDecision);

        // Build Commercetools actions based on Forter decision/recommendations and app configuration (set custom fields and/or set order state).
        $decisionActions = $processor->getOrderDecisionActions($order, $forterResponse);

        $forterDecisionActionConfig = UtilsHelper::getForterDecisionActionConfig($forterDecision, 'post');
        preg_match('/(SET_ORDER_STATE)\s*:\s*([^\s]+)/msi', $forterDecisionActionConfig, $setOrderStateAction);
        if (!empty($setOrderStateAction[1]) && !empty($setOrderStateAction[2])) {
            $this->assertForterDecisionActionsSetOrderState($decisionActions, 'post', $forterDecision, $setOrderStateAction[2]);
            return $this;
        }

        if (!$forterDecisionActionConfig || $forterDecisionActionConfig === 'DO_NOTHING') {
            $this->assertForterDecisionActionsDoNothing($decisionActions, 'post', $forterDecision);
            return $this;
        }

        throw new \Exception("Unsupported configured post decision action: " . $forterDecisionActionConfig);
    }

    //========================================================================//

    /**
     * @method checkForterPreDecisionActionsForPaymentMethodBlockOrderPlace
     * @param  string                                                  $paymentMethod
     * @param  string                                                  $forterDecision
     * @param  $this                                                   $forterDecision
     */
    public function checkForterPreDecisionActionsForPaymentMethodBlockOrderPlace($paymentMethod, $forterDecision)
    {
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock($paymentMethod, $forterDecision, false, false);
        $response = $this->makeCommercetoolsApiExtensionsRequest($orderMock);
        $this->assertForterDecisionActionsBlockOrderPlaceResponse($response);
        return $this;
    }

    /**
     * @method checkForterPreDecisionActionsForPaymentMethodDoNothing
     * @param  string                                                  $paymentMethod
     * @param  string                                                  $forterDecision
     * @param  $this                                                   $forterDecision
     */
    public function checkForterPreDecisionActionsForPaymentMethodDoNothing($paymentMethod, $forterDecision)
    {
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock($paymentMethod, $forterDecision, false, false);
        $response = $this->makeCommercetoolsApiExtensionsRequest($orderMock);
        $this->assertForterDecisionActionsDoNothingResponse($response, 'pre', $forterDecision);
        return $this;
    }

    /**
     * @method checkForterPreDecisionActionsForPaymentMethodSetOrderState
     * @param  string                                                  $paymentMethod
     * @param  string                                                  $forterDecision
     * @param  $this                                                   $forterDecision
     */
    public function checkForterPreDecisionActionsForPaymentMethodSetOrderState($paymentMethod, $forterDecision)
    {
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock($paymentMethod, $forterDecision, false, false);
        $response = $this->makeCommercetoolsApiExtensionsRequest($orderMock);
        $this->assertForterDecisionActionsSetOrderStateResponse($response, 'pre', $forterDecision);
        return $this;
    }

    //========================================================================//

    /**
     * @method checkForterPostDecisionActionsForPaymentMethod
     * @param  string                                                  $paymentMethod
     * @param  string                                                  $forterDecision
     * @param  $this                                                   $forterDecision
     */
    public function checkForterPostDecisionActionsForPaymentMethod($paymentMethod, $forterDecision)
    {
        $this->initConfig();
        $orderMock = ForterMockGenerator::getDecisionReadyPaymentMethodOrderMock($paymentMethod, $forterDecision, true, true);
        $this->assertOrderPostDecisionExpectedActions($orderMock, $forterDecision);
        return $this;
    }
}
