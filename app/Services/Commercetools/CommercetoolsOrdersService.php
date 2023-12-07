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

use Commercetools\Api\Models\Order\OrderFromCartDraftBuilder;
use Commercetools\Api\Models\Order\OrderSetCustomTypeActionBuilder;
use Commercetools\Api\Models\Order\OrderUpdateActionBuilder;
use Commercetools\Api\Models\Order\OrderUpdateActionCollection;
use Commercetools\Api\Models\Order\OrderUpdateBuilder;

use Commercetools\Exception\BadRequestException;
use Commercetools\Exception\NotFoundException;

class CommercetoolsOrdersService extends CommercetoolsClientService
{
    public static function getAll($expand = null, $limit = null)
    {
        $request = parent::getApiClientBuilder()
            ->orders()->get();

        if ($expand) {
            $request = $request->withExpand($expand);
        }

        if ($limit) {
            $request = $request->withLimit(1);
        }

        return $request->execute();
    }

    public static function getById($id, $expand = null)
    {
        $request = parent::getApiClientBuilder()
            ->orders()->withId($id)->get();

        if ($expand) {
            $request = $request->withExpand($expand);
        }

        return $request->execute();
    }

    public static function getByOrderNumber($orderNumber, $expand = null)
    {
        $request = parent::getApiClientBuilder()
            ->orders()->withOrderNumber($orderNumber)->get();

        if ($expand) {
            $request = $request->withExpand($expand);
        }

        return $request->execute();
    }

    public static function getOrderByPaymentId($paymentId, $expand = null)
    {
        $request = parent::getApiClientBuilder()
            ->orders()->get()
            ->withWhere('paymentInfo(payments(id=:paymentId))')
            ->withPredicateVar("paymentId", $paymentId)
            ->withLimit(1);

        if ($expand) {
            $request = $request->withExpand($expand);
        }

        $results = $request->execute()->getResults();
        return !empty($results) ? $results[0] : false;
    }

    public static function setCustomTypeById($id, $customType, $fields = [], $version = 1)
    {
        $orderSetCustomTypeAction = OrderSetCustomTypeActionBuilder::of()
            ->withType(
                TypeResourceIdentifierBuilder::of()
                    ->withId($customType)
                    ->withKey($customType)
                    ->build()
            );

        if ($fields) {
            $fieldContainer = FieldContainerBuilder::of();
            foreach ($fields as $field => $value) {
                $fieldContainer->addValue($field, $value);
            }
            $fieldContainer->build();
            $orderSetCustomTypeAction->withFields();
        }

        return $orderSetCustomTypeAction->build();
    }

    public static function updateById($id, $actions = [], $version = 1)
    {
        try {
            $orderUpdateActionCollection = new OrderUpdateActionCollection();
            foreach ($actions as $action) {
                $orderUpdateActionCollection->add(
                    OrderUpdateActionBuilder::of()->build()->fromArray($action)
                );
            }
            $orderUpdate = OrderUpdateBuilder::of()
              ->withVersion($version)
              ->withActions($orderUpdateActionCollection)
              ->build();

            return parent::getApiClientBuilder()
                ->orders()->withId($id)->post($orderUpdate)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function create($id, $version, $orderNumber)
    {
        $orderFromCartDraft = OrderFromCartDraftBuilder::of()
            ->withId($id)
            ->withVersion($version)
            ->withOrderNumber($orderNumber)
            ->build();

        return parent::getApiClientBuilder()
            ->orders()->post($orderFromCartDraft)->execute();
    }
}
