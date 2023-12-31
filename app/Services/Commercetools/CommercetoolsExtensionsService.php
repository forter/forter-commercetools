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

use Commercetools\Api\Models\Extension\ExtensionDraftBuilder;
use Commercetools\Api\Models\Extension\ExtensionDestinationBuilder;
use Commercetools\Api\Models\Extension\ExtensionTriggerBuilder;
use Commercetools\Api\Models\Extension\ExtensionTriggerCollection;
use Commercetools\Api\Models\Extension\ExtensionUpdateActionBuilder;
use Commercetools\Api\Models\Extension\ExtensionUpdateActionCollection;
use Commercetools\Api\Models\Extension\ExtensionUpdateBuilder;
use Commercetools\Api\Models\Extension\ExtensionChangeDestinationActionBuilder;
use Commercetools\Api\Models\Extension\ExtensionChangeTriggersActionBuilder;

use Commercetools\Exception\BadRequestException;
use Commercetools\Exception\NotFoundException;

class CommercetoolsExtensionsService extends CommercetoolsClientService
{
    public static function get($expand = null, $limit = null)
    {
        $request = parent::getApiClientBuilder()
            ->extensions()->get();

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
                ->extensions()->withId($id)->get()->execute();
        } catch (NotFoundException $e) {
            //Do nothing
        }
    }

    public static function getByKey($key)
    {
        try {
            return parent::getApiClientBuilder()
                ->extensions()->withKey($key)->get()->execute();
        } catch (NotFoundException $e) {
            //Do nothing
        }
    }

    public static function deleteById($id, $version = 1)
    {
        try {
            return parent::getApiClientBuilder()
                ->extensions()->withId($id)->delete()->withVersion($version)->execute();
        } catch (NotFoundException $e) {
            //Do nothing
        }
    }

    public static function deleteByKey($key, $version = 1)
    {
        try {
            return parent::getApiClientBuilder()
                ->extensions()->withKey($key)->delete()->withVersion($version)->execute();
        } catch (NotFoundException $e) {
            //Do nothing
        }
    }

    public static function updateById($id, $actions = [], $version = 1)
    {
        try {
            $extensionUpdate = ExtensionUpdateBuilder::of()
                ->withVersion($version)
                ->withActions(self::buildExtensionUpdateActionCollection($actions))
                ->build();

            return parent::getApiClientBuilder()
                ->extensions()->withId($id)->post($extensionUpdate)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function updateByKey($key, $actions = [], $version = 1)
    {
        try {
            $extensionUpdate = ExtensionUpdateBuilder::of()
                ->withVersion($version)
                ->withActions(self::buildExtensionUpdateActionCollection($actions))
                ->build();

            return parent::getApiClientBuilder()
                ->extensions()->withKey($key)->post($extensionUpdate)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function buildExtensionUpdateActionCollection($actions)
    {
        $extensionUpdateActionCollection = new ExtensionUpdateActionCollection();
        foreach ($actions as $key => $action) {
            switch ($action['action']) {
                case 'changeDestination':
                    $extensionUpdateActionCollection->add(
                        ExtensionChangeDestinationActionBuilder::of()
                            ->withDestination(self::buildExtensionDestination($action['destination']))
                            ->build()
                    );
                    break;

                case 'changeTriggers':
                    $extensionUpdateActionCollection->add(
                        ExtensionChangeTriggersActionBuilder::of()
                            ->withTriggers(self::buildExtensionTriggersCollection($action['triggers']))
                            ->build()
                    );
                    break;

                default:
                    $extensionUpdateActionCollection->add(
                        ExtensionUpdateActionBuilder::of()->build()->fromArray($action)
                    );
                    break;
            }
        }
        return $extensionUpdateActionCollection;
    }

    public static function buildExtensionDestination($destination)
    {
        return ExtensionDestinationBuilder::of()
            ->build()->fromArray($destination);
    }

    public static function buildExtensionTriggersCollection($triggers)
    {
        $extensionTriggersCollection = new ExtensionTriggerCollection();
        foreach ($triggers as $trigger) {
            $extensionTriggersCollection->add(
                ExtensionTriggerBuilder::of()->build()->fromArray($trigger)
            );
        }
        return $extensionTriggersCollection;
    }

    public static function create($key, $destination, $triggers, $timeoutInMs = null)
    {
        $extensionDraft = ExtensionDraftBuilder::of()
            ->withKey($key)
            ->withDestination(self::buildExtensionDestination($destination))
            ->withTriggers(self::buildExtensionTriggersCollection($triggers))
            ->withTimeoutInMs($timeoutInMs)
            ->build();

        return parent::getApiClientBuilder()
            ->extensions()->post($extensionDraft)->execute();
    }
}
