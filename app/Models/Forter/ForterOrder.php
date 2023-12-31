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

use Illuminate\Support\Carbon;
use Commercetools\Exception\NotFoundException;
use App\Services\Commercetools\CommercetoolsCartsService;
use App\Services\Commercetools\CommercetoolsOrdersService;
use App\Services\Commercetools\CommercetoolsPaymentsService;
use App\Models\Forter\ForterCommercetoolsModel;
use App\Models\Forter\ForterCart;
use App\Models\Forter\ForterPayment;

/**
 * ForterOrder model
 */
class ForterOrder extends ForterCommercetoolsModel
{
    /**
     * @var string
     */
    public const EXPECTED_COMMERCETOOLS_TYPE = 'Order';

    /**
     * Forter Cart model
     * @var ForterCart
     */
    private $cart;

    /**
     * Forter Payment models
     * @var ForterPayment[]
     */
    private $payments;

    /**
     * Forter order ID to be used on API paths and schemas (limited to 40 chars)
     * @var string|int|null
     */
    private $forterOrderId;

    /**
     * @method getData
     * @return array
     */
    public function getData($refresh = false)
    {
        $payments = [];
        foreach ($this->getPayments($refresh) as $payment) {
            $payments[] = [
                'obj' => $payment
            ];
        }
        return array_replace(
            $this->getOrderData(),
            [
                'paymentInfo' => [
                    'payments' => $payments,
                ],
            ],
            [
                'cart' => [
                    'obj' => $this->getCart($refresh),
                ],
            ],
            [
                'forterOrderId' => $this->getForterOrderId($refresh),
            ]
        );
    }

    /**
     * @method resetData
     * @return self  $this
     */
    protected function resetData()
    {
        $this->cart = null;
        $this->payments = null;
        $this->forterOrderId = null;
        return parent::resetData();
    }

    /**
     * Pull order data from Commercetools
     * @method loadById
     * @param  int    $orderId
     * @return ForterOrder  $this
     */
    public static function loadById($orderId)
    {
        $order = CommercetoolsOrdersService::getById($orderId);
        return self::getInstance($order);
    }

    /**
     * @method getOrderData
     * @return array
     */
    public function getOrderData()
    {
        return $this->_data ?: [];
    }

    /**
     * @method getCart
     * @param  bool         $refresh
     * @return ForterCart
     */
    public function getCart($refresh = false)
    {
        if (is_null($this->cart) || $refresh) {
            $cart = [];
            if (!empty($this->_data['cart']['id'])) {
                if (empty($this->_data['cart']['obj'])) {
                    try {
                        $cart = CommercetoolsCartsService::getById($this->_data['cart']['id']);
                    } catch (NotFoundException $e) {
                        // Ignore errors if cart not found (carts may be deleted after a while)
                    }
                } else {
                    $cart = $this->_data['cart']['obj'];
                }
            }
            $this->cart =  ForterCart::getInstance($cart);
        }

        return $this->cart;
    }

    /**
     * @method getPayments
     * @param  bool         $refresh
     * @return ForterPayment[]
     */
    public function getPayments($refresh = false)
    {
        if (is_null($this->payments) || $refresh) {
            if (isset($this->_data['paymentInfo']['payments'])) {
                $this->payments = [];
                foreach ((array) $this->_data['paymentInfo']['payments'] as $payment) {
                    if (empty($payment['obj'])) {
                        $payment = CommercetoolsPaymentsService::getById($payment['id']);
                    } else {
                        $payment = $payment['obj'];
                    }
                    $this->payments[] = ForterPayment::getInstance($payment);
                }
            }
        }

        return $this->payments ?: [];
    }

    /**
     * @method getBillingAddress
     * @return array
     */
    public function getBillingAddress()
    {
        return array_replace(
            self::DEFAULT_COMMERCETOOLS_ADDRESS_FIELDS,
            isset($this->_data['billingAddress']) ? $this->_data['billingAddress'] : []
        );
    }

    /**
     * @method getShippingAddress
     * @return array
     */
    public function getShippingAddress()
    {
        return array_replace(
            self::DEFAULT_COMMERCETOOLS_ADDRESS_FIELDS,
            isset($this->_data['shippingAddress']) ? $this->_data['shippingAddress'] : []
        );
    }

    /**
     * @method getCreatedAtTimestampMs
     * @return string|null
     */
    public function getCreatedAtTimestampMs()
    {
        return isset($this->_data['createdAt']) ? Carbon::parse($this->_data['createdAt'])->getTimestampMs() : null;
    }

    /**
     * @method getCustomerEmail
     * @return string|null
     */
    public function getCustomerEmail()
    {
        return isset($this->_data['customerEmail']) ? $this->_data['customerEmail'] : null;
    }

    /**
     * @method getLineItems
     * @return array
     */
    public function getLineItems()
    {
        return isset($this->_data['lineItems']) ? $this->_data['lineItems'] : [];
    }

    /**
     * @method getTaxedPriceTotalGross
     * @return mixed
     */
    public function getTaxedPriceTotalGross($field)
    {
        if ($field) {
            return isset($this->_data['taxedPrice']['totalGross'][$field]) ? $this->_data['taxedPrice']['totalGross'][$field] : null;
        }
        return isset($this->_data['taxedPrice']['totalGross']) ? $this->_data['taxedPrice']['totalGross'] : [];
    }

    /**
     * @method getTotalPrice
     * @return mixed
     */
    public function getTotalPrice($field)
    {
        if ($field) {
            return isset($this->_data['totalPrice'][$field]) ? $this->_data['totalPrice'][$field] : null;
        }
        return isset($this->_data['totalPrice']) ? $this->_data['totalPrice'] : [];
    }

    /**
     * @method getShippingInfo
     * @return mixed
     */
    public function getShippingInfo()
    {
        return isset($this->_data['shippingInfo']) ? $this->_data['shippingInfo'] : [];
    }

    /**
     * Get orderId to be used on API paths and schemas (limited to 40 chars)
     * @method getForterOrderId
     * @param  bool         $refresh
     * @return string|int
     */
    public function getForterOrderId($refresh = false)
    {
        if (is_null($this->forterOrderId) || $refresh) {
            $fieldName = config('forter_schema_mapping.order.orderId', 'id');
            $this->forterOrderId = \substr(!empty($this->_data[$fieldName]) ? $this->_data[$fieldName] : $this->_data['id'], 0, 40);
        }
        return $this->forterOrderId;
    }

    public function getForterOrderStatusByOrderState()
    {
        $orderState = !empty($this->_data['orderState']) ? strtolower($this->_data['orderState']) : null;

        switch ($orderState) {
            case 'open':        // The default state of a new Order.
            case 'confirmed':   // Indicates that the Order is accepted and being processed.
                $orderState = "PROCESSING";
                break;

            case 'complete':   // Indicates that the Order is fulfilled.
                $orderState = "COMPLETED";
                break;

            case 'cancelled':  // Indicates that the Order is canceled.
                $orderState = "CANCELED_BY_MERCHANT";
                break;

            default:
                $orderState = "PROCESSING";
                break;
        }

        return $orderState;
    }

    /**
     * @method getForterSchemaPaymentField
     * @param  string                            $forterFieldPath
     * @param  string                            $defaultValue
     * @param  bool                              $fallbackToCartCustomFields
     * @return mixed
     */
    public function getForterSchemaOrderField($forterFieldPath, $defaultValue = '', $fallbackToCartCustomFields = true)
    {
        $customFieldName = (string) config(sprintf('forter_schema_mapping.order.%s', $forterFieldPath), $forterFieldPath);
        if (\strpos($customFieldName, '.') !== false) {
            $customFieldName = \explode('.', $customFieldName);
            switch ($customFieldName[0]) {
                case 'order':
                    return isset($this->_data[$customFieldName[1]]) ? $this->_data[$customFieldName[1]] : $defaultValue;
                    break;

                case 'custom':
                    $customFieldName = $customFieldName[1];
                    break;

                default:
                    return $defaultValue;
                    break;
            }
        }

        $value = $this->getCustomField($customFieldName);
        if (!$value && $fallbackToCartCustomFields) {
            $value = $this->getCart()->getCustomField($customFieldName);
        }

        return !is_null($value) ? $value : $defaultValue;
    }

    /**
     * @method hasPayments
     * @return bool
     */
    public function hasPayments()
    {
        return isset($this->_data['paymentInfo']['payments']) && count($this->_data['paymentInfo']['payments']);
    }

    /**
     * @method hasPaymentTransactions
     * @return bool
     */
    public function hasPaymentTransactions($refresh = false)
    {
        foreach ($this->getPayments($refresh) as $payment) {
            if (!empty($payment->getTransactions())) {
                return true;
            }
        }
        return false;
    }
}
