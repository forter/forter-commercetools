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

use Commercetools\Api\Models\Subscription\SubscriptionDraftBuilder;
use Commercetools\Api\Models\Subscription\DestinationBuilder;
use Commercetools\Api\Models\Subscription\MessageSubscriptionBuilder;
use Commercetools\Api\Models\Subscription\MessageSubscriptionCollection;
use Commercetools\Api\Models\Subscription\ChangeSubscriptionBuilder;
use Commercetools\Api\Models\Subscription\ChangeSubscriptionCollection;
use Commercetools\Api\Models\Subscription\SubscriptionUpdateActionBuilder;
use Commercetools\Api\Models\Subscription\SubscriptionUpdateActionCollection;
use Commercetools\Api\Models\Subscription\SubscriptionUpdateBuilder;
use Commercetools\Api\Models\Subscription\SubscriptionChangeDestinationActionBuilder;
use Commercetools\Api\Models\Subscription\SubscriptionSetMessagesActionBuilder;
use Commercetools\Api\Models\Subscription\SubscriptionSetChangesActionBuilder;

use Commercetools\Exception\BadRequestException;
use Commercetools\Exception\NotFoundException;

class CommercetoolsSubscriptionsService extends CommercetoolsClientService
{
    public static function get($expand = null, $limit = null)
    {
        $request = parent::getApiClientBuilder()
            ->subscriptions()->get();

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
        try {
            return parent::getApiClientBuilder()
                ->subscriptions()->withId($id)->get()->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function getByKey($key)
    {
        try {
            return parent::getApiClientBuilder()
                ->subscriptions()->withKey($key)->get()->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function deleteById($id, $version = 1)
    {
        try {
            return parent::getApiClientBuilder()
                ->subscriptions()->withId($id)->delete()->withVersion($version)->execute();
        } catch (NotFoundException $e) {
            return 'NotFoundException';
        }
    }

    public static function deleteByKey($key, $version = 1)
    {
        try {
            return parent::getApiClientBuilder()
                ->subscriptions()->withKey($key)->delete()->withVersion($version)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function updateById($id, $actions = [], $version = 1)
    {
        try {
            $subscriptionUpdate = SubscriptionUpdateBuilder::of()
                ->withVersion($version)
                ->withActions(self::buildSubscriptionUpdateActionCollection($actions))
                ->build();

            return parent::getApiClientBuilder()
                ->subscriptions()->withId($id)->post($subscriptionUpdate)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function updateByKey($key, $actions = [], $version = 1)
    {
        try {
            $subscriptionUpdate = SubscriptionUpdateBuilder::of()
                ->withVersion($version)
                ->withActions(self::buildSubscriptionUpdateActionCollection($actions))
                ->build();

            return parent::getApiClientBuilder()
                ->subscriptions()->withKey($key)->post($subscriptionUpdate)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function buildSubscriptionUpdateActionCollection($actions)
    {
        $subscriptionUpdateActionCollection = new SubscriptionUpdateActionCollection();
        foreach ($actions as $key => $action) {
            switch ($action['action']) {
                case 'changeDestination':
                    $subscriptionUpdateActionCollection->add(
                        SubscriptionChangeDestinationActionBuilder::of()
                            ->withDestination(self::buildSubscriptionDestination($action['destination']))
                            ->build()
                    );
                    break;

                case 'setMessages':
                    $subscriptionUpdateActionCollection->add(
                        SubscriptionSetMessagesActionBuilder::of()
                            ->withMessages(self::buildSubscriptionMessagesCollection($action['messages']))
                            ->build()
                    );
                    break;

                case 'setChanges':
                    $subscriptionUpdateActionCollection->add(
                        SubscriptionSetChangesActionBuilder::of()
                            ->withChanges(self::buildChangeSubscriptionCollection($action['changes']))
                            ->build()
                    );
                    break;

                default:
                    $subscriptionUpdateActionCollection->add(
                        SubscriptionUpdateActionBuilder::of()->build()->fromArray($action)
                    );
                    break;
            }
        }
        return $subscriptionUpdateActionCollection;
    }

    public static function buildSubscriptionDestination($destination)
    {
        return  DestinationBuilder::of()
            ->build()->fromArray($destination);
    }

    public static function buildSubscriptionMessagesCollection($messages)
    {
        $subscriptionMessagesCollection = new MessageSubscriptionCollection();
        foreach ($messages as $message) {
            $subscriptionMessagesCollection->add(
                MessageSubscriptionBuilder::of()
                    ->withResourceTypeId($message['resourceTypeId'])
                    ->withTypes($message['types'])
                    ->build()
            );
        }
        return $subscriptionMessagesCollection;
    }

    public static function buildChangeSubscriptionCollection($changes)
    {
        $subscriptionChangesCollection = new ChangeSubscriptionCollection();
        foreach ($changes as $change) {
            $subscriptionChangesCollection->add(
                ChangeSubscriptionBuilder::of()
                    ->withResourceTypeId($change)
                    ->build()
            );
        }
        return $subscriptionChangesCollection;
    }

    public static function create($key, $destination, $messages, $changes = null)
    {
        $subscriptionDraft = SubscriptionDraftBuilder::of()
            ->withKey($key)
            ->withDestination(self::buildSubscriptionDestination($destination))
            ->withMessages(self::buildSubscriptionMessagesCollection($messages));

        if ($changes) {
            $subscriptionDraft = $subscriptionDraft->withChanges(self::buildChangeSubscriptionCollection($changes));
        }

        $subscriptionDraft = $subscriptionDraft->build();

        return parent::getApiClientBuilder()
            ->subscriptions()->post($subscriptionDraft)->execute();
    }
}
