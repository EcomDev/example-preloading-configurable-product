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
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;


class ConfigurableSalable implements DataLoader
{
    public const DATA_KEY = 'configurable_salable';

    private StockResolverInterface $stockResolver;
    private StockIndexTableNameResolverInterface $indexTableNameResolver;
    private StoreManagerInterface $storeManager;
    private ResourceConnection $resourceConnection;

    public function __construct(
        StockResolverInterface $stockResolver,
        StockIndexTableNameResolverInterface $indexTableNameResolver,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection
    ) {
        $this->stockResolver = $stockResolver;
        $this->indexTableNameResolver = $indexTableNameResolver;
        $this->storeManager = $storeManager;
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

        $websiteCode = $this->storeManager->getWebsite($filter->getWebsiteId())->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $connection = $this->resourceConnection->getConnection('catalog');
        $relationTable = $this->resourceConnection->getTableName('catalog_product_super_link','catalog');
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity','catalog');
        $indexTableName = $this->indexTableNameResolver->execute((int)$stock->getStockId());
        $select = $connection->select();

        $select
            ->from(['relation' => $relationTable], [])
            ->join(['product' => $productTable], 'relation.product_id = product.entity_id', [])
            ->join(['index' => $indexTableName], 'product.sku = index.sku', [])
            ->columns([
                'entity_id' => 'relation.parent_id',
                'is_salable' => 'MAX(index.is_salable)',
            ])
            ->group('relation.parent_id')
            ->where('relation.parent_id IN(?)', $configurableProductIds);
        ;

        return $connection->fetchAssoc($select);
    }

    public function isApplicable(string $type): bool
    {
        return true;
    }
}
