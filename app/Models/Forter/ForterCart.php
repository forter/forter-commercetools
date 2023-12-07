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

use App\Services\Commercetools\CommercetoolsCartsService;
use App\Models\Forter\ForterCommercetoolsModel;

/**
 * ForterCart model
 */
class ForterCart extends ForterCommercetoolsModel
{
    /**
     * @var string
     */
    public const EXPECTED_COMMERCETOOLS_TYPE = 'Cart';

    /**
     * @method getData
     * @return array
     */
    public function getData($refresh = false)
    {
        return $this->getCartData();
    }

    /**
     * Pull cart data from Commercetools
     * @method loadById
     * @param  int    $cartId
     * @return ForterCart
     */
    public static function loadById($cartId)
    {
        $cart = CommercetoolsCartsService::getById($cartId);
        return self::getInstance($cart);
    }

    /**
     * @method getCartData
     * @return array
     */
    public function getCartData()
    {
        return $this->_data ?: [];
    }
}
