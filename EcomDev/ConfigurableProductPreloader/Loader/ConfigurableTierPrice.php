<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace EcomDev\ConfigurableProductPreloader\Loader;

use EcomDev\ProductDataPreLoader\DataService\DataLoader;
use EcomDev\ProductDataPreLoader\DataService\ScopeFilter;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ResourceConnection;

/**
 * ConfigurableTierPrice
 */
class ConfigurableTierPrice implements DataLoader
{
    public const DATA_KEY = 'configurable_tier_price';

    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function load(ScopeFilter $filter, array $products): array
    {
        $configurableProductIds = [];

        foreach ($products as $product) {
            if ($product->isType(Configurable::TYPE_CODE)) {
                $configurableProductIds[] = $product->getId();
            }
        }

        if (!$configurableProductIds) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection('catalog');
        $tierPriceTable = $this->resourceConnection->getTableName('catalog_product_entity_tier_price','catalog');
        $relationTable = $this->resourceConnection->getTableName('catalog_product_super_link','catalog');
        $select = $connection->select();

        $select
            ->from(['tier_price' => $tierPriceTable], [])
            ->join(['relation' => $relationTable], 'relation.product_id = tier_price.entity_id', [])
            ->columns([
                'entity_id' => 'relation.parent_id',
                'tier_price' => 'MIN(tier_price.value)'
            ])
            ->group('relation.parent_id')
            ->where('relation.parent_id IN(?)', $configurableProductIds)
            ->where('tier_price.customer_group_id = ? OR tier_price.all_groups = 1', $filter->getCustomerGroupId())
            ->where('tier_price.website_id = ?', $filter->getWebsiteId())
        ;

        return $connection->fetchAssoc($select);
    }

    public function isApplicable(string $type): bool
    {
        return $type === self::TYPE_LIST;
    }
}
