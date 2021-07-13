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

class ConfigurablePrice implements DataLoader
{
    public const DATA_KEY = 'configurable_price';

    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /** @inheritDoc */
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
        $priceIndexTable = $this->resourceConnection->getTableName('catalog_product_index_price','catalog');
        $relationTable = $this->resourceConnection->getTableName('catalog_product_super_link','catalog');
        $select = $connection->select();

        $select
            ->from(['index' => $priceIndexTable], [])
            ->join(['relation' => $relationTable], 'relation.product_id = index.entity_id', [])
            ->columns([
                'entity_id' => 'relation.parent_id',
                'price' => 'MIN(index.price)',
                'final_price' => 'MIN(index.final_price)',
            ])
            ->group('relation.parent_id')
            ->where('relation.parent_id IN(?)', $configurableProductIds)
            ->where('index.customer_group_id = ?', $filter->getCustomerGroupId())
            ->where('index.website_id = ?', $filter->getWebsiteId())
        ;

        return $connection->fetchAssoc($select);
    }

    public function isApplicable(string $type): bool
    {
        return $type === self::TYPE_LIST;
    }
}
