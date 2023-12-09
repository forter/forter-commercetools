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

use App\Services\Commercetools\CommercetoolsPaymentsService;
use App\Models\Forter\ForterCommercetoolsModel;

/**
 * ForterPayment model
 */
class ForterPayment extends ForterCommercetoolsModel
{
    /**
     * @var string
     */
    public const EXPECTED_COMMERCETOOLS_TYPE = 'Payment';

    /**
     * Forter payment schema type (e.g., 'creditCard')
     * @var string
     */
    private $forterPaymentSchemaType;

    /**
     * @method dataValidationOnSet
     * @param  array|object   $data
     * @return self           $this
     */
    protected function dataValidationOnSet($data)
    {
        if (!isset($data['paymentMethodInfo'])) {
            return parent::dataValidationOnSet($data);
        }
        return $this;
    }

    /**
     * @method getData
     * @return array
     */
    public function getData($refresh = false)
    {
        return $this->getPaymentData();
    }

    /**
     * Pull payment data from Commercetools
     * @method loadById
     * @param  int    $paymentId
     * @return ForterPayment
     */
    public static function loadById($paymentId)
    {
        $payment = CommercetoolsPaymentsService::getById($paymentId);
        return self::getInstance($payment);
    }

    /**
     * @method getPayment
     * @return array
     */
    public function getPaymentData()
    {
        return $this->_data ?: [];
    }

    /**
     * @method getAmountPlanned
     * @param  string|null    $field
     * @return mixed
     */
    public function getAmountPlanned($field = null)
    {
        if ($field) {
            return isset($this->_data['amountPlanned'][$field]) ? $this->_data['amountPlanned'][$field] : null;
        }
        return isset($this->_data['amountPlanned']) ? $this->_data['amountPlanned'] : [];
    }

    /**
     * @method getTransactions
     * @return array
     */
    public function getTransactions()
    {
        return isset($this->_data['transactions']) ? $this->_data['transactions'] : [];
    }

    /**
     * Get payment method interface (payment method)
     * @method getPaymentInterface
     * @param  string    $default
     * @return string
     */
    public function getPaymentInterface($default = '')
    {
        return !empty($this->_data['paymentMethodInfo']['paymentInterface']) ? $this->_data['paymentMethodInfo']['paymentInterface'] : $default;
    }

    /**
     * @method getPaymentMethod
     * @param  string    $default
     * @return string
     */
    public function getPaymentMethod($default = '')
    {
        return !empty($this->_data['paymentMethodInfo']['method']) ? $this->_data['paymentMethodInfo']['method'] : $default;
    }

    /**
     * @method getForterPaymentSchemaType
     * @param bool     $refresh
     * @return string
     */
    public function getForterPaymentSchemaType($refresh = false)
    {
        if (is_null($this->forterPaymentSchemaType) || $refresh) {
            $paymentMethod = trim($this->getPaymentMethod());
            $schemaType = config(sprintf('forter_schema_mapping.payment_method_schema_type.%s', preg_replace('/\s+/', '_', $paymentMethod)));
            if ($schemaType) {
                $this->forterPaymentSchemaType = $schemaType;
            } else {
                $paymentMethod = \strtolower($paymentMethod);
                $paymentInterface = \strtolower($this->getPaymentInterface());
                if (\strpos($paymentMethod, 'paypal') !== false || \strpos($paymentInterface, 'paypal') !== false) {
                    $this->forterPaymentSchemaType = 'paypal';
                } elseif (\strpos($paymentMethod, 'applepay') !== false || \strpos($paymentInterface, 'applepay') !== false) {
                    $this->forterPaymentSchemaType = 'applePay';
                } elseif (
                    \strpos($paymentMethod, 'googlepay') !== false || \strpos($paymentMethod, 'androidpay') !== false ||
                    \strpos($paymentInterface, 'googlepay') !== false || \strpos($paymentInterface, 'androidpay') !== false
                ) {
                    $this->forterPaymentSchemaType = 'androidPay';
                } elseif (
                    \strpos($paymentMethod, 'paybright') !== false || \strpos($paymentInterface, 'paybright') !== false ||
                    \strpos($paymentMethod, 'klarna_account') !== false || \strpos($paymentInterface, 'klarna_account') !== false
                ) {
                    $this->forterPaymentSchemaType = 'installmentService';
                } else {
                    $this->forterPaymentSchemaType = 'creditCard';
                }
            }
        }

        return $this->forterPaymentSchemaType;
    }

    /**
     * @method getForterSchemaPaymentField
     * @param  string                            $forterFieldPath
     * @param  string                            $defaultValue
     * @return mixed
     */
    public function getForterSchemaPaymentField($forterFieldPath, $defaultValue = '')
    {
        // look for paymentInterface specific mapping for schema type
        if (\strpos($forterFieldPath, '.') === false) {
            $customFieldName = config(sprintf('forter_schema_mapping.payment.%s.%s.%s', $this->getForterPaymentSchemaType(), $this->getPaymentInterface(), $forterFieldPath), null);
        }
        // look for specific mapping for schema type
        if (empty($customFieldName)) {
            $customFieldName = config(sprintf('forter_schema_mapping.payment.%s.%s', $this->getForterPaymentSchemaType(), $forterFieldPath), $forterFieldPath);
        }
        $customFieldName = (string) $customFieldName;
        if (\strpos($customFieldName, '.') !== false) {
            $customFieldName = \explode('.', $customFieldName);
            switch ($customFieldName[0]) {
                case 'payment':
                    return isset($this->_data[$customFieldName[1]]) ? $this->_data[$customFieldName[1]] : $defaultValue;
                    break;

                case 'paymentMethodInfo':
                    return isset($this->_data['paymentMethodInfo'][$customFieldName[1]]) ? $this->_data['paymentMethodInfo'][$customFieldName[1]] : $defaultValue;
                    break;

                case 'custom':
                    $customFieldName = $customFieldName[1];
                    break;

                default:
                    return $defaultValue;
                    break;
            }
        }

        return $this->getCustomField($customFieldName, $defaultValue);
    }
}
