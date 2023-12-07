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
use App\Helpers\UtilsHelper;

/**
 * ForterCommercetoolsModel model
 */
class ForterCommercetoolsModel extends ForterAbstractModel
{
    /**
     * @var string
     */
    public const EXPECTED_COMMERCETOOLS_TYPE = null;

    public const DEFAULT_COMMERCETOOLS_ADDRESS_FIELDS = [
        'firstName' => '',
        'lastName' => '',
        'streetName' => '',
        'streetNumber' => '',
        'additionalStreetInfo' => '',
        'city' => '',
        'country' => '',
        'region' => '',
        'postalCode' => '',
        'phone' => '',
        'mobile' => '',
        'email' => '',
    ];

    /**
     * @method setData
     * @param  array|object   $data
     * @return self           $this
     */
    public function setData($data)
    {
        $data = UtilsHelper::toArrayRecursive($data);
        $data = !empty($data['obj']['id']) ? $data['obj'] : $data;
        $this->dataValidationOnSet($data);
        $this->resetData();
        $this->_data = $data;
        return $this;
    }

    /**
     * @method dataValidationOnSet
     * @param  array|object   $data
     * @return self           $this
     */
    protected function dataValidationOnSet($data)
    {
        $type = isset($data['type']) ? $data['type'] : null;
        if (static::EXPECTED_COMMERCETOOLS_TYPE && $type && $type !== static::EXPECTED_COMMERCETOOLS_TYPE) {
            throw new \Exception("Can't instatiate " . __CLASS__ . ". Expected data[type] to be '" . static::EXPECTED_COMMERCETOOLS_TYPE . "', '{$type}' given.", 1);
        }
        return $this;
    }

    /**
     * @method getId
     * @return string|int|null
     */
    public function getId()
    {
        return isset($this->_data['id']) ? $this->_data['id'] : null;
    }

    /**
     * @method getType
     * @return string|null
     */
    public function getType()
    {
        return isset($this->_data['type']) ? $this->_data['type'] : null;
    }

    /**
     * @method getVersion
     * @return string|int|null
     */
    public function getVersion()
    {
        return isset($this->_data['version']) ? $this->_data['version'] : null;
    }

    /**
     * Get custom fields
     * @method getCustomFields
     * @return array
     */
    public function getCustomFields()
    {
        return (isset($this->_data['custom']['fields'])) ? $this->_data['custom']['fields'] : [];
    }

    /**
     * Get custom field by field name
     * @method getCustomField
     * @param  string         $field
     * @param  mixed         $default
     * @return mixed
     */
    public function getCustomField($field, $default = null)
    {
        return (isset($this->_data['custom']['fields'][$field])) ? $this->_data['custom']['fields'][$field] : $default;
    }

    /**
     * @method getForterResponse
     * @return array|string
     */
    public function getForterResponse($decode = true)
    {
        $forterResponse = $this->getCustomField('forterResponse');
        return $decode ? (array) \json_decode((string)$forterResponse, true) : $forterResponse;
    }

    /**
     * @method hasForterPreAuthResponse
     * @return bool
     */
    public function hasForterPreAuthResponse()
    {
        return \strpos($this->getCustomField('forterResponse', ''), 'pre_auth_') !== false;
    }

    /**
     * @method hasForterPostAuthResponse
     * @return bool
     */
    public function hasForterPostAuthResponse()
    {
        return \strpos($this->getCustomField('forterResponse', ''), 'post_auth_') !== false;
    }
}
