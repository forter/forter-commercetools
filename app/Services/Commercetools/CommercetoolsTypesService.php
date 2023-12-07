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
use Commercetools\Api\Models\Type\FieldDefinitionBuilder;
use Commercetools\Api\Models\Type\FieldDefinitionCollection;
use Commercetools\Api\Models\Type\TypeDraftBuilder;
use Commercetools\Api\Models\Type\TypeUpdateActionBuilder;
use Commercetools\Api\Models\Type\TypeUpdateActionCollection;
use Commercetools\Api\Models\Type\TypeUpdateBuilder;
use Commercetools\Api\Models\Type\TypeChangeNameActionBuilder;
use Commercetools\Api\Models\Type\TypeSetDescriptionActionBuilder;
use Commercetools\Api\Models\Type\TypeChangeLabelActionBuilder;
use Commercetools\Api\Models\Type\TypeAddFieldDefinitionActionBuilder;

use Commercetools\Exception\BadRequestException;
use Commercetools\Exception\NotFoundException;

class CommercetoolsTypesService extends CommercetoolsClientService
{
    public static function getAll($expand = null, $limit = null)
    {
        $request = parent::getApiClientBuilder()
            ->types()->get();

        if ($expand) {
            $request = $request->withExpand($expand);
        }

        if ($limit) {
            $request = $request->withLimit(1);
        }

        return $request->execute();
    }

    public static function getById($id)
    {
        return parent::getApiClientBuilder()
            ->types()->withId($id)->get()->execute();
    }

    public static function getByKey($key)
    {
        try {
            return parent::getApiClientBuilder()
                ->types()->withKey($key)->get()->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function deleteById($id, $version = 1)
    {
        try {
            return parent::getApiClientBuilder()
                ->types()->withId($id)->delete()->withVersion($version)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function deleteByKey($key, $version = 1)
    {
        try {
            return parent::getApiClientBuilder()
                ->types()->withKey($key)->delete()->withVersion($version)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function updateById($id, $actions = [], $version = 1)
    {
        try {
            $typeUpdate = TypeUpdateBuilder::of()
                ->withVersion($version)
                ->withActions(self::buildTypeUpdateActionCollection($actions))
                ->build();

            return parent::getApiClientBuilder()
                ->types()->withId($id)->post($typeUpdate)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function updateByKey($key, $actions = [], $version = 1)
    {
        try {
            $typeUpdate = TypeUpdateBuilder::of()
                ->withVersion($version)
                ->withActions(self::buildTypeUpdateActionCollection($actions))
                ->build();

            return parent::getApiClientBuilder()
                ->types()->withKey($key)->post($typeUpdate)->execute();
        } catch (NotFoundException $e) {
            // Do nothing
        }
    }

    public static function buildTypeUpdateActionCollection($actions)
    {
        $typeUpdateActionCollection = new TypeUpdateActionCollection();
        foreach ($actions as $key => $action) {
            switch ($action['action']) {
                case 'changeName':
                    $typeUpdateActionCollection->add(
                        TypeChangeNameActionBuilder::of()
                            ->withName(LocalizedStringBuilder::fromArray($action['name'])->build())
                            ->build()
                    );
                    break;

                case 'setDescription':
                    $typeUpdateActionCollection->add(
                        TypeSetDescriptionActionBuilder::of()
                            ->withDescription(LocalizedStringBuilder::fromArray($action['description'])->build())
                            ->build()
                    );
                    break;

                case 'changeLabel':
                    $typeUpdateActionCollection->add(
                        TypeChangeLabelActionBuilder::of()
                            ->withFieldName($action['fieldName'])
                            ->withLabel(LocalizedStringBuilder::fromArray($action['label'])->build())
                            ->build()
                    );
                    break;

                case 'addFieldDefinition':
                    $typeUpdateActionCollection->add(
                        TypeAddFieldDefinitionActionBuilder::of()
                            ->withFieldDefinition(FieldDefinitionBuilder::of()->build()->fromArray($action['fieldDefinition']))
                            ->build()
                    );
                    break;

                default:
                    $typeUpdateActionCollection->add(
                        TypeUpdateActionBuilder::of()->build()->fromArray($action)
                    );
                    break;
            }
        }
        return $typeUpdateActionCollection;
    }

    public static function buildFieldDefinitionCollection($fieldDefinitions)
    {
        $fieldDefinitionCollection = new FieldDefinitionCollection();
        foreach ($fieldDefinitions as $fieldDefinition) {
            $fieldDefinitionCollection->add(
                FieldDefinitionBuilder::of()->build()->fromArray($fieldDefinition)
            );
        }
        return $fieldDefinitionCollection;
    }

    public static function create($key, $name, $description, $resourceTypeIds, $fieldDefinitions)
    {
        $typeDraft = TypeDraftBuilder::of()
            ->withKey($key)
            ->withName(LocalizedStringBuilder::fromArray($name)->build())
            ->withDescription(LocalizedStringBuilder::fromArray($description)->build())
            ->withResourceTypeIds($resourceTypeIds)
            ->withFieldDefinitions(self::buildFieldDefinitionCollection($fieldDefinitions))
            ->build();

        return parent::getApiClientBuilder()
            ->types()->post($typeDraft)->execute();
    }
}
