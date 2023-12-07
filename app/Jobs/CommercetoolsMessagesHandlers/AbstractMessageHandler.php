<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Jobs\CommercetoolsMessagesHandlers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Forter\ForterCommercetoolsMessage;
use App\Models\Forter\ForterOrder;
use App\Helpers\UtilsHelper;

abstract class AbstractMessageHandler implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 10;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 600;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    //public $timeout = 300;

    /**
     * Message ID
     * @var string|int
     */
    protected $messageId;

    /**
     * Message payload
     * @var array
     */
    protected $messagePayload;

    /**
     * Message model
     * @var ForterCommercetoolsMessage
     */
    protected $messageModel;

    /**
     * @return void
     */
    public function __construct(ForterCommercetoolsMessage $messageModel)
    {
        $this->messageId = $messageModel->getMessageId();
        $this->messagePayload = $messageModel->getMessageData();
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->init();
            if ($this->isForterDisabled()) {
                return;
            }
            $this->logHandleStart();
            $result = $this->handleMessage();
            $this->logHandleEnd();
            return $result;
        } catch (\Exception $e) {
            $this->logHandleException($e);
        }
    }

    /**
     * @return void
     */
    abstract protected function handleMessage();

    /**
     * @return void
     */
    public function init()
    {
        $this->messageModel = ForterCommercetoolsMessage::getInstance($this->getMessagePayload());
    }

    /**
     * Get Commercetools message ID
     * @method getMessageId
     * @return string|int
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Get Commercetools message payload
     * @method getMessagePayload
     * @return array
     */
    public function getMessagePayload()
    {
        return $this->messagePayload;
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
     * Get full message data
     * @method getMessageData
     * @param  string      $key  Extract value by key from the root level
     * @return mixed
     */
    public function getMessageData($key = null)
    {
        return $this->getMessageModel()->getMessageData($key);
    }

    /**
     * Get message type (e.g. 'OrderCreated')
     * @method getMessageType
     * @return string
     */
    public function getMessageType()
    {
        return $this->getMessageModel()->getMessageType();
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
     * Get resource type (e.g. 'order')
     * @method getResourceType
     * @return string|int
     */
    public function getResourceType()
    {
        return $this->getMessageModel()->getResourceType();
    }

    /**
     * Get resource (e.g. order payload) if exists
     * @method getResource
     * @return array|object|null
     */
    public function getResourceData()
    {
        return $this->getMessageModel()->getResourceData();
    }

    /**
     * Get ForterOrder loaded with resource data (use when resource is known to be of type 'order')
     * @method getOrderModel
     * @param  bool               $refresh
     * @return ForterOrder
     */
    public function getOrderModel($refresh = false)
    {
        return $this->getMessageModel()->getOrderModel($refresh);
    }

    /**
     * Set order model
     * @method getOrderModel
     * @param  array|ForterOrder               $order
     * @return ForterCommercetoolsMessage      $this
     */
    public function setOrderModel($order)
    {
        $this->getMessageModel()->setOrderModel($order);
        return $this;
    }

    /**
     * @method logHandleStart
     * @return void
     */
    protected function logHandleStart()
    {
        Log::info("[CommercetoolsMessagesHandlers\Handle{$this->getMessageType()}Message] [START] | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()}");
    }

    /**
     * @method logHandleEnd
     * @return void
     */
    protected function logHandleEnd()
    {
        Log::info("[CommercetoolsMessagesHandlers\Handle{$this->getMessageType()}Message] [END] | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()}");
    }

    /**
     * @method logHandleException
     * @param  Exception             $e
     * @return void
     */
    protected function logHandleException($e)
    {
        Log::error("[CommercetoolsMessagesHandlers\Handle{$this->getMessageType()}Message] [ERROR] {$e->getMessage()}", ['exception' => $e, 'messageId' => $this->getMessageId(), 'messageType' => $this->getMessageType(), 'resourceType' => $this->getResourceType(), 'resourceId' => $this->getResourceId(), 'messagePayload' => $this->getMessagePayload()]);
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed($e)
    {
        Log::error('[' . __CLASS__ . '] [Exception] ' . $e->getMessage(), ['trace' => $e->getTrace(), 'messageId' => $this->getMessageId()]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [
            new WithoutOverlapping(),
        ];
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return "CT_MSG_ID_{$this->getMessageId()}";
    }

    /**
     * @return bool
     */
    public function isForterDisabled()
    {
        if (!UtilsHelper::isForterEnabled()) {
            Log::warning("[CommercetoolsMessagesHandlers\Handle{$this->getMessageType()}Message] [SKIPPING] " . UtilsHelper::APP_IS_DISABLED_MSG . " | Message ID:{$this->getMessageId()} | Message Type:{$this->getMessageType()}");
            return true;
        }
        return false;
    }
}
