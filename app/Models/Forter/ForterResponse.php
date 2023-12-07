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

use App\Models\Forter\ForterAbstractModel;

/**
 * ForterResponse model
 */
class ForterResponse extends ForterAbstractModel
{
    /**
     * @var string
     */
    private $message = '';

    /**
     * @var string
     */
    private $action = '';

    /**
     * @var string
     */
    private $reasonCode = '';

    /**
     * @var string
     */
    private $status = '';

    /**
     * @var string
     */
    private $decision = '';

    /**
     * @var array
     */
    private $recommendations = [];

    /**
     * @var array
     */
    private $recommendationMessages = [];

    /**
     * @method getData
     * @return array
     */
    public function getData()
    {
        return [
            '_response' => $this->getResponseData(),
            'message' => $this->getMessage(),
            'action' => $this->getAction(),
            'reasonCode' => $this->getReasonCode(),
            'status' => $this->getStatus(),
            'decision' => $this->getDecision(),
            'recommendations' => $this->getRecommendations(),
            'recommendationMessages' => $this->getRecommendationMessages(),
        ];
    }

    /**
     * @method resetData
     * @return self  $this
     */
    protected function resetData()
    {
        $this->message = '';
        $this->action = '';
        $this->reasonCode = '';
        $this->status = '';
        $this->decision = '';
        $this->recommendations = [];
        return parent::resetData();
    }

    /**
     * @method setData
     * @param  array|object    $data
     * @return ForterResponse  $this
     */
    public function setData($data)
    {
        parent::setData($data);

        $this->message = !empty($this->_data['message']) ? $this->_data['message'] : '';
        $this->action = !empty($this->_data['action']) ? strtolower($this->_data['action']) : '';
        $this->reasonCode = !empty($this->_data['reasonCode']) ? $this->_data['reasonCode'] : '';
        $this->status = !empty($this->_data['status']) ? strtolower($this->_data['status']) : '';

        // Extract decision from response
        if (
            //!isset($this->_data['action']) ||
            !isset($this->_data['status']) ||
            $this->status !== 'success'
        ) {
            $this->decision = 'error';
        } else {
            $this->decision = $this->action;
        }

        // Extract recommendations from response
        $this->recommendations = [];
        $this->recommendationMessages = [];
        if (!empty($this->_data['recommendations']) && is_array($this->_data['recommendations'])) {
            foreach ($this->_data['recommendations'] as $recommendation) {
                if (!$recommendation || !is_string($recommendation)) {
                    continue;
                }
                $this->recommendations[] = $recommendation;
                $this->recommendationMessages[$recommendation] = config(
                    sprintf('forter.recommendation_keys_messages_map.%s', $recommendation),
                    $recommendation
                );
            }
        }

        return $this;
    }

    /**
     * @method getResponse
     * @return array
     */
    public function getResponseData()
    {
        return $this->_data ?: [];
    }

    /**
     * @method getMessage
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @method getAction
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @method getReasonCode
     * @return string
     */
    public function getReasonCode()
    {
        return $this->reasonCode;
    }

    /**
     * @method getStatus
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @method getDecision
     * @return string
     */
    public function getDecision()
    {
        return $this->decision;
    }

    /**
     * @method getRecommendations
     * @return array
     */
    public function getRecommendations()
    {
        return $this->recommendations;
    }

    /**
     * @method getRecommendationMessages
     * @return array
     */
    public function getRecommendationMessages()
    {
        return $this->recommendationMessages;
    }
}
