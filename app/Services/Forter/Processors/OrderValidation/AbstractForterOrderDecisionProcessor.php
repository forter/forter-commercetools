<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Services\Forter\Processors\OrderValidation;

use Illuminate\Support\Facades\Log;
use App\Services\Commercetools\CommercetoolsOrdersService;
use App\Models\Forter\ForterCommercetoolsMessage;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterResponse;
use App\Services\Forter\ForterApiService;
use App\Services\Forter\SchemaBuilders\ForterDecisionActionsBuilder;
use App\Helpers\UtilsHelper;

abstract class AbstractForterOrderDecisionProcessor
{
    /**
     * 'pre' / 'post'
     * @var string
     */
    public const AUTH_STEP = '';

    /**
     * Message model
     * @var ForterCommercetoolsMessage
     */
    protected $messageModel;

    /**
     * Can Process Order?
     * @var bool
     */
    protected $canProcessOrder;

    /**
     * @var string
     */
    protected $forterDecision;

    /**
     * @var ForterResponse
     */
    protected $forterResponse;

    /**
     * @var array
     */
    protected $configuredForterDecisionAction = 'DO_NOTHING';

    /**
     * @var array
     */
    protected $decisionActions = [];

    /**
     * @return void
     */
    public function __construct(ForterCommercetoolsMessage $messageModel)
    {
        $this->messageModel = $messageModel;
    }

    /**
     * Set Forter Commercetools message model
     * @method setMessageModel
     * @return static
     */
    public function setMessageModel(ForterCommercetoolsMessage $messageModel)
    {
        $this->canProcessOrder = null;
        $this->forterDecision = null;
        $this->forterResponse = null;
        $this->configuredForterDecisionAction = 'DO_NOTHING';
        $this->decisionActions = [];

        $this->messageModel = $messageModel;

        return $this;
    }

    /**
     * Get Forter Commercetools message model
     * @method getMessageModel
     * @return ForterCommercetoolsMessage
     */
    public function getMessageModel()
    {
        return $this->messageModel;
    }

    /**
     * Get Commercetools message ID
     * @method getMessageId
     * @return string|int
     */
    public function getMessageId()
    {
        return $this->messageModel->getMessageId();
    }

    /**
     * Get resource id (e.g. 'OrderCreated')
     * @method getMessageType
     * @return string
     */
    public function getMessageType()
    {
        return $this->messageModel->getMessageType();
    }

    /**
     * Get resource type (e.g. 'order')
     * @method getResourceType
     * @return string|int
     */
    public function getResourceType()
    {
        return $this->getMessageModel()->getResourceType();
    }


    /**
     * Get resource id (e.g. order ID)
     * @method getResourceId
     * @return string|int
     */
    public function getResourceId()
    {
        return $this->getMessageModel()->getResourceId();
    }

    /**
     * Get message trigger (e.g. 'Order/Create')
     * @method getMessageTrigger
     * @return string
     */
    public function getMessageTrigger()
    {
        return $this->messageModel->getMessageTrigger();
    }

    /**
     * Get ForterOrder loaded with resource data from the message
     * @method getMessageType
     * @return ForterOrder
     */
    public function getOrderModel()
    {
        return $this->getMessageModel()->getOrderModel();
    }

    /**
     * Get ForterOrder loaded with resource data from the message
     * @method getForterOrderId
     * @return string|int
     */
    public function getForterOrderId()
    {
        return $this->getOrderModel()->getForterOrderId();
    }

    /**
     * Set order model
     * @method setOrderModel
     * @param  array|ForterOrder               $order
     * @return ForterCommercetoolsMessage      $this
     */
    public function setOrderModel($order)
    {
        return $this->getMessageModel()->setOrderModel($order);
    }

    /**
     * Return 'pre' / 'post'
     * @method getAuthStep
     * @return string
     */
    public function getAuthStep()
    {
        return static::AUTH_STEP;
    }

    /**
     * @method getForterDecision
     * @return string|null
     */
    public function getForterDecision()
    {
        return $this->forterDecision;
    }

    /**
     * @method getForterResponse
     * @return ForterResponse
     */
    public function getForterResponse()
    {
        return $this->forterResponse;
    }

    /**
     * @method getConfiguredForterDecisionAction
     * @return string
     */
    public function getConfiguredForterDecisionAction()
    {
        return $this->configuredForterDecisionAction;
    }

    /**
     * @method getPreparedCommercetoolsUpdateActions
     * @return array
     */
    public function getPreparedCommercetoolsUpdateActions()
    {
        return !empty($this->decisionActions['actions']) ? $this->decisionActions['actions'] : [];
    }

    /**
     * @method getPreparedCommercetoolsErrors
     * @return array
     */
    public function getPreparedCommercetoolsErrors()
    {
        return !empty($this->decisionActions['errors']) ? $this->decisionActions['errors'] : [];
    }

    /**
     * @method getInfoLogSuffix
     * @return string
     */
    public function getInfoLogSuffix()
    {
        if ($this->getMessageType()) {
            return " | Message Type:{$this->getMessageType()}";
        } elseif ($this->getMessageTrigger()) {
            return " | Trigger:{$this->getMessageTrigger()}";
        } else {
            return "";
        }
    }

    /**
     * Get new model instance
     * @param  array    $data
     * @return self  $this
     */
    public static function getInstance($data)
    {
        return new static($data);
    }

    /**
     * Get empty new model instance
     * @param  array    $data
     * @return self  $this
     */
    public static function getEmptyInstance()
    {
        return new static(
            ForterCommercetoolsMessage::getInstance([])
        );
    }

    /**
     * @method canProcessOrder
     * @param  ForterOrder     $order
     * @return bool
     */
    abstract public function canProcessOrder(ForterOrder $order);

    /**
     * @method process
     * @return array|false
     * ['order' => ForterOrder, 'forter_decision' => string, 'forter_response' => ForterResponse, 'decision_actions' => array]
     */
    abstract public function process();

    /**
     * @method getOrderDecisionActions
     * @param  ForterOrder                     $order
     * @return array
     */
    public function getOrderDecisionActions(ForterOrder $order, ForterResponse $forterResponse)
    {
        $this->orderModel = $order;
        $this->forterResponse = $forterResponse;
        $this->forterDecision = $forterResponse->getDecision();
        $this->configuredForterDecisionAction = UtilsHelper::getForterDecisionActionConfig($this->forterDecision, $this->getAuthStep());

        Log::info("Order ID: {$order->getForterOrderId()} | Auth step: {$this->getAuthStep()} | Forter decision: {$this->forterDecision} | Configured decision action: {$this->configuredForterDecisionAction}{$this->getInfoLogSuffix()}");

        // Build Commercetools actions based on Forter decision/recommendations and app configuration (set custom fields and/or set order state).
        $this->decisionActions = ForterDecisionActionsBuilder::buildCommercetoolsDecisionActions($order, $this->getAuthStep(), $forterResponse);

        Log::debug("Order ID: {$order->getForterOrderId()} | Auth step: {$this->getAuthStep()} | Forter decision: {$this->forterDecision}{$this->getInfoLogSuffix()}", ['forter_config_decision_action' => $this->configuredForterDecisionAction, 'prepared_actions' => $this->decisionActions]);

        return $this->decisionActions;
    }
}
