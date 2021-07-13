<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace EcomDev\ConfigurableProductPreloader\Plugin;

use EcomDev\ConfigurableProductPreloader\Loader\ConfigurablePrice;
use EcomDev\ProductDataPreLoader\DataService\LoadService;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurablePriceResolver;
use Magento\Framework\Pricing\SaleableInterface;

class ConfigurablePriceResolverPlugin
{
    private LoadService $loadService;

    public function __construct(LoadService $loadService)
    {
        $this->loadService = $loadService;
    }

    public function aroundResolvePrice(ConfigurablePriceResolver $subject, callable $proceed, SaleableInterface $product)
    {
        $productId = (int)$product->getId();

        if ($this->loadService->has($productId, ConfigurablePrice::DATA_KEY)) {
            return (float)($this->loadService->get($productId, ConfigurablePrice::DATA_KEY)['final_price'] ?? 0);
        }

        return $proceed($product);
    }
}
