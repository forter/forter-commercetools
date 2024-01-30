<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Services\Commercetools;

use Commercetools\Base\JsonObjectModel;
use Commercetools\Api\Models\Cart\CartUpdateActionBuilder;
use Commercetools\Api\Models\Cart\CartUpdateActionCollection;
use Commercetools\Api\Models\Cart\CartUpdateBuilder;
use Commercetools\Api\Models\Cart\ReplicaCartDraftBuilder;

use Commercetools\Exception\BadRequestException;
use Commercetools\Exception\NotFoundException;

class CommercetoolsCartsService extends CommercetoolsClientService
{
    public static function get($expand = null, $limit = null)
    {
        $request = parent::getApiClientBuilder()
            ->carts()->get();

        if ($expand) {
            $request = $request->withExpand($expand);
        }

        if ($limit) {
            $request = $request->withLimit($limit);
        }

        return $request->execute();
    }

    public static function getById($id)
    {
        return parent::getApiClientBuilder()
            ->carts()->withId($id)->get()->execute();
    }

    public static function deleteById($id, $version = 1)
    {
        try {
            return parent::getApiClientBuilder()
                ->carts()->withId($id)->delete()->withVersion($version)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function updateById($id, $actions = [], $version = 1)
    {
        try {
            $cartUpdateActionCollection = new CartUpdateActionCollection();
            foreach ($actions as $action) {
                $cartUpdateActionCollection->add(
                    CartUpdateActionBuilder::of()->build()->fromArray($action)
                );
            }
            $cartUpdate = CartUpdateBuilder::of()
              ->withVersion($version)
              ->withActions($cartUpdateActionCollection)
              ->build();

            return parent::getApiClientBuilder()
                ->carts()->withId($id)->post($cartUpdate)->execute();
        } catch (NotFoundException $e) {
            return 'NotFoundException';
        }
    }

    public static function replicateCart($id, $typeId = "order")
    {
        $replicaCartDraft = ReplicaCartDraftBuilder::of()
            ->withReference(JsonObjectModel::fromArray([
                "typeId" => $typeId,
                "id" => $id,
            ]))
            ->build();

        return parent::getApiClientBuilder()
            ->carts()
            ->replicate()
            ->post($replicaCartDraft)
            ->execute();
    }

    /**
     * [addPayment description]
     * @method addPayment
     * @param  object|int   $cart           Cart CT model or cart ID.
     * @param  int          $paymentId      Payment ID
     */
    public static function addPayment($cart, $paymentId)
    {
        if (is_numeric($cart)) {
            $cart = self::getById($cart);
        }

        return self::updateById(
            $cart->getId(),
            [
                [
                    "action" => "addPayment",
                    "payment" => [
                        "id" => $paymentId,
                        "typeId" => "payment",
                    ]
                ],
            ],
            $cart->getVersion(),
        );
    }
}
