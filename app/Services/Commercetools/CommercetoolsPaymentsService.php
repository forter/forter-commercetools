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

use Commercetools\Api\Models\Common\LocalizedStringBuilder;
use Commercetools\Api\Models\Common\MoneyBuilder;
use Commercetools\Api\Models\Payment\PaymentDraftBuilder;
use Commercetools\Api\Models\Payment\PaymentMethodInfoBuilder;
use Commercetools\Api\Models\Payment\PaymentUpdateActionBuilder;
use Commercetools\Api\Models\Payment\PaymentUpdateActionCollection;
use Commercetools\Api\Models\Payment\PaymentUpdateBuilder;
use Commercetools\Api\Models\Payment\TransactionDraftBuilder;
use Commercetools\Api\Models\Payment\TransactionDraftCollection;
use Commercetools\Api\Models\Type\CustomFieldsDraftBuilder;
use Commercetools\Api\Models\Type\FieldContainerBuilder;
use Commercetools\Api\Models\Type\TypeResourceIdentifierBuilder;

use Commercetools\Exception\BadRequestException;
use Commercetools\Exception\NotFoundException;

class CommercetoolsPaymentsService extends CommercetoolsClientService
{
    public static function get($expand = null, $limit = null)
    {
        $request = parent::getApiClientBuilder()
            ->payments()->get();

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
            ->payments()->withId($id)->get()->execute();
    }

    public static function deleteById($id, $version = 1)
    {
        try {
            return parent::getApiClientBuilder()
                ->payments()->withId($id)->delete()->withVersion($version)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function updateById($id, $actions = [], $version = 1)
    {
        try {
            $paymentUpdateActionCollection = new PaymentUpdateActionCollection();
            foreach ($actions as $action) {
                $paymentUpdateActionCollection->add(
                    PaymentUpdateActionBuilder::of()->build()->fromArray($action)
                );
            }
            $paymentUpdate = PaymentUpdateBuilder::of()
              ->withVersion($version)
              ->withActions($paymentUpdateActionCollection)
              ->build();

            return parent::getApiClientBuilder()
                ->payments()->withId($id)->post($paymentUpdate)->execute();
        } catch (NotFoundException $e) {
            return 'NotFoundException';
        }
    }

    public static function addPaymentTransaction($paymentId, $transaction, $version = 1)
    {
        return self::updateById(
            $paymentId,
            [
                [
                    "action" => "addTransaction",
                    "transaction" => TransactionDraftBuilder::of()->build()->fromArray($transaction)
                ],
            ],
            $version
        );
    }

    public static function create($data)
    {
        $paymentDraft = PaymentDraftBuilder::of();
        $paymentDraft->withKey($data['key']);
        $paymentDraft->withInterfaceId($data['interfaceId']);

        $paymentDraft->withAmountPlanned(
            MoneyBuilder::of()
                ->withCurrencyCode($data['amountPlanned']['currencyCode'])
                ->withCentAmount($data['amountPlanned']['centAmount'])
                ->build()
        );

        $paymentDraft->withPaymentMethodInfo(
            PaymentMethodInfoBuilder::of()
                ->withPaymentInterface($data['paymentMethodInfo']['paymentInterface'])
                ->withMethod($data['paymentMethodInfo']['method'])
                ->withName(LocalizedStringBuilder::fromArray($data['paymentMethodInfo']['name'])->build())
                ->build()
        );

        if (!empty($data['transactions'])) {
            $transactionDraftCollection = new TransactionDraftCollection();
            foreach ($data['transactions'] as $transaction) {
                $transactionDraftCollection->add(
                    TransactionDraftBuilder::of()->build()->fromArray($transaction)
                );
            }
            $paymentDraft->withTransactions($transactionDraftCollection);
        }

        if (!empty($data['custom'])) {
            $paymentDraft->withCustom(
                CustomFieldsDraftBuilder::of()
                    ->withType(TypeResourceIdentifierBuilder::of()->withKey($data['custom']['type']['key'])->build())
                    ->withFields(FieldContainerBuilder::fromArray($data['custom']['fields'])->build())
                    ->build()
            );
        }

        $paymentDraft = $paymentDraft->build();

        return parent::getApiClientBuilder()
            ->payments()->post($paymentDraft)->execute();
    }
}
