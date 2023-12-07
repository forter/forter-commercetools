<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Models\Forter;

use App\Models\Forter\ForterCommercetoolsModel;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterPayment;

/**
 * ForterCommercetoolsMessage model
 */
class ForterCommercetoolsMessage extends ForterCommercetoolsModel
{
    /**
     * Commercetools Message ID
     * Available on subscription messages
     * @var string|int
     */
    private $messageId;

    /**
     * Commercetools Message Type (e.g. 'OrderCreated')
     * Available on subscription messages
     * @var string
     */
    private $messageType;

    /**
     * Commercetools Action (e.g. 'Create')
     * Available on API-Extension messages
     * @var string
     */
    private $messageAction;

    /**
     * Commercetools Trigger (e.g. 'Order/Create')
     * Available on API-Extension messages
     * @var string
     */
    private $messageTrigger;

    /**
     * Commercetools Resource ID (e.g. Order ID)
     * @var string|int
     */
    private $resourceId;

    /**
     * Commercetools Resource typeId (e.g. 'order')
     * @var string
     */
    private $resourceType;

    /**
     * Commercetools Resource (e.g. order payload)
     * @var array|object
     */
    private $resourceData;

    /**
     * Prepared (when requested) ForterOrder instance with order resource data
     * @var ForterOrder
     */
    private $orderModel;

    /**
     * Prepared (when requested) ForterPayment instance with payment resource data
     * @var ForterPayment
     */
    private $paymentModel;

    /**
     * @method setData
     * @param  array|object    $data
     * @return ForterResponse  $this
     */
    public function setData($data)
    {
        parent::setData($data);

        $this->resourceId = $data['resource']['id'];
        $this->resourceType = $data['resource']['typeId'];

        // Subscription Messages
        $this->messageId = isset($data['id']) ? $data['id'] : null;
        $this->messageType = isset($data['type']) ? $data['type'] : null;

        // API-Extensions
        $this->messageAction = isset($data['action']) ? $data['action'] : null;
        $this->messageTrigger = $this->resourceType && $this->messageAction ? ucfirst(strtolower($this->resourceType)) . '/' . ucfirst(strtolower($this->messageAction)) : null;

        return $this;
    }

    /**
     * @method getData
     * @return array
     */
    public function getData()
    {
        return $this->getMessageData();
    }

    /**
     * @method resetData
     * @return self  $this
     */
    protected function resetData()
    {
        $this->resourceId = null;
        $this->resourceType = null;
        $this->messageId = null;
        $this->messageType = null;
        $this->messageAction = null;
        $this->messageTrigger = null;
        $this->resourceData = null;
        $this->orderModel = null;
        $this->paymentModel = null;
        return parent::resetData();
    }

    /**
     * @method getMessageData
     * @param  string      $key  Extract value by key from the root level
     * @return array|mixed
     */
    public function getMessageData($key = null)
    {
        if ($key) {
            return isset($this->_data[$key]) ? $this->_data[$key] : null;
        }
        return $this->_data ?: [];
    }

    /**
     * Get Commercetools message ID
     * Available on subscription messages
     * @method getMessageId
     * @return string|int
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Get message type (e.g. 'OrderCreated')
     * Available on subscription messages
     * @method getMessageType
     * @return string|int
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * Get message action (e.g. 'Create')
     * Available on API-Extension messages
     * @method getMessageAction
     * @return string
     */
    public function getMessageAction()
    {
        return $this->messageAction;
    }

    /**
     * Get message trigger (e.g. 'Order/Create')
     * Available on API-Extension messages
     * @method getMessageTrigger
     * @return string
     */
    public function getMessageTrigger()
    {
        return $this->messageTrigger;
    }

    /**
     * Get resource id (e.g. order ID)
     * @method getResourceId
     * @return string|int
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * Get resource type (e.g. 'order')
     * @method getResourceType
     * @return string|int
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Set resource object (if needed)
     * @method setResource
     * @param  mixed                       $resource
     * @return ForterCommercetoolsMessage  $this
     */
    public function setResource($resource)
    {
        $this->resourceData = $resource;
        return $this;
    }

    /**
     * Get resource (e.g. order payload) if exists
     * @method getResource
     * @return array|object|null
     */
    public function getResourceData()
    {
        // Loaded resource
        if (!is_null($this->resourceData)) {
            return $this->resourceData;
        }
        // Subscription Message
        if (isset($this->_data[$this->getResourceType()])) {
            return $this->_data[$this->getResourceType()];
        }
        // API-Extension
        if (isset($this->_data['resource']['obj'])) {
            return $this->_data['resource']['obj'];
        }

        return null;
    }

    /**
     * Get ForterOrder loaded with resource data (use when resource is known to be of type 'order')
     * @method getOrderModel
     * @param  bool               $refresh
     * @return ForterOrder
     */
    public function getOrderModel($refresh = false)
    {
        if (is_null($this->orderModel) || $refresh) {
            $this->orderModel = ForterOrder::getInstance($this->getResourceData());
        }
        return $this->orderModel;
    }

    /**
     * Set order model
     * @method setOrderModel
     * @param  array|ForterOrder               $order
     * @return ForterCommercetoolsMessage      $this
     */
    public function setOrderModel($order)
    {
        $this->orderModel = is_a($order, ForterOrder::class) ? $order : ForterOrder::getInstance($order);
        return $this;
    }

    /**
     * Get ForterPayment loaded with resource data (use when resource is known to be of type 'payment')
     * @method getPaymentModel
     * @param  bool               $refresh
     * @return ForterPayment
     */
    public function getPaymentModel($refresh = false)
    {
        if (is_null($this->paymentModel) || $refresh) {
            $this->paymentModel = ForterPayment::getInstance($this->getResourceData());
        }
        return $this->paymentModel;
    }
}
