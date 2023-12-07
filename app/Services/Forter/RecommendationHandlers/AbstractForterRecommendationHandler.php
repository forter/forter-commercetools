<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Services\Forter\RecommendationHandlers;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterResponse;
use App\Helpers\UtilsHelper;

abstract class AbstractForterRecommendationHandler
{
    /**
     * Recommendation Code
     * @var string
     */
    protected $recommendation;

    /**
     * Forter Response
     * @var ForterOrder
     */
    protected $forterResponse;

    /**
     * Order
     * @var ForterOrder
     */
    protected $order;

    /**
     * Auth step / timing ('pre'/'post')
     * @var string
     */
    protected $authStep;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $recommendation,
        ForterResponse $forterResponse,
        ForterOrder $order,
        string $authStep
    ) {
        $this->recommendation = $recommendation;
        $this->forterResponse = $forterResponse;
        $this->order = $order;
        $this->authStep = $authStep;
    }

    /**
     * Handle recommendation and maybe return array with actions/errors.
     * @return array
     */
    public function handle()
    {
        try {
            if ($this->isForterDisabled()) {
                return;
            }
            $this->logHandleStart();
            $handlerActions = $this->handleRecommendation();
            $this->logHandleEnd();
            return $handlerActions;
        } catch (\Exception $e) {
            $this->logHandleException($e);
        }
        return [];
    }

    /**
     * Handle recommendation and maybe return array with actions/errors.
     * @return array
     *//* Examples:

        // Nothing special, proceed normally
        return [];

        // Return errors with a 400 http code (will work on pre-auth only)
        return [
            'errors' => [
                [
                    "code" => "InvalidOperation",
                    "message" => 'Some error message...',
                ],
            ],
        ];

        // Return actions that would be added to the default/configured decision actions
        return [
            'actions' => [
                [
                    "code" => "changeOrderState",
                    "orderState" => 'Cancelled',
                ],
            ],
        ];

        // Return actions that would run *instead* of the default/configured decision actions
        return [
            'only' = true,
            'actions' => [
                [
                    "code" => "changeOrderState",
                    "orderState" => 'Cancelled',
                ],
            ],
        ];

        // "Do Nothing" action (prevent default/configured actions)
        return [
            'only' = true,
            'actions' => [],
        ];
     */
    abstract protected function handleRecommendation();

    /**
     * @method getRecommendation
     * @return string
     */
    protected function getRecommendation()
    {
        return $this->recommendation;
    }

    /**
     * @method getForterResponse
     * @return ForterResponse
     */
    protected function getForterResponse()
    {
        return $this->forterResponse;
    }

    /**
     * @method getOrder
     * @return ForterOrder
     */
    protected function getOrder()
    {
        return $this->order;
    }

    /**
     * @method getAuthStep
     * @return string
     */
    protected function getAuthStep()
    {
        return $this->authStep;
    }

    /**
     * @method logHandleStart
     * @return void
     */
    protected function logHandleStart()
    {
        Log::info("[Forter recommendation handler] [START] | Recommendation:{$this->getRecommendation()} | Order ID: {$this->getOrder()->getForterOrderId()} | Auth Step:{$this->getAuthStep()}");
    }

    /**
     * @method logHandleEnd
     * @return void
     */
    protected function logHandleEnd()
    {
        Log::info("[Forter recommendation handler] [END] | Recommendation:{$this->getRecommendation()} | Order ID: {$this->getOrder()->getForterOrderId()} | Auth Step:{$this->getAuthStep()}");
    }

    /**
     * @method logHandleException
     * @param  Exception             $e
     * @return void
     */
    protected function logHandleException($e)
    {
        Log::error("[Forter recommendation handler] [ERROR] {$e->getMessage()}", ['exception' => $e, 'recommendation' => $this->getRecommendation(), 'forterResponse' => $this->getForterResponse(), 'authStep' => $this->getAuthStep(), 'order' => $this->getOrder()]);
    }

    /**
     * @return bool
     */
    public function isForterDisabled()
    {
        if (!UtilsHelper::isForterEnabled()) {
            Log::warning("[Forter recommendation handler] Recommendation:{$this->getRecommendation()} [SKIPPING] " . UtilsHelper::APP_IS_DISABLED_MSG . " | Order ID: {$this->getOrder()->getForterOrderId()} | Auth Step:{$this->getAuthStep()}");
            return true;
        }
        return false;
    }
}
