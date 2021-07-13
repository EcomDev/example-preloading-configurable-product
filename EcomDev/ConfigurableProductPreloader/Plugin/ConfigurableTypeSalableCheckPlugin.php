<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace EcomDev\ConfigurableProductPreloader\Plugin;

use EcomDev\ConfigurableProductPreloader\Loader\ConfigurableSalable;
use EcomDev\ProductDataPreLoader\DataService\LoadService;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ConfigurableTypeSalableCheckPlugin
{
    private LoadService $loadService;

    public function __construct(LoadService $loadService)
    {
        $this->loadService = $loadService;
    }

    public function aroundIsSalable(Configurable $subject, callable $proceed, Product $product)
    {
        $productId = (int)$product->getId();

        if (!$this->loadService->has($productId, ConfigurableSalable::DATA_KEY)) {
            return $proceed($product);
        }
        $isSalable = ((int)$product->getStatus()) === Product\Attribute\Source\Status::STATUS_ENABLED;
        $isSalable = $isSalable
            && $this->loadService->get($productId, ConfigurableSalable::DATA_KEY)['is_salable'] ?? false;

        return $isSalable;
    }
}
