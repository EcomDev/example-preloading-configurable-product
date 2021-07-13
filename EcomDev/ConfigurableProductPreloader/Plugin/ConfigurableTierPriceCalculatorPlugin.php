<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace EcomDev\ConfigurableProductPreloader\Plugin;

use EcomDev\ConfigurableProductPreloader\Loader\ConfigurableTierPrice;
use EcomDev\ProductDataPreLoader\DataService\LoadService;
use Magento\Catalog\Pricing\Price\MinimalTierPriceCalculator;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Pricing\SaleableInterface;

class ConfigurableTierPriceCalculatorPlugin
{
    private LoadService $loadService;

    public function __construct(LoadService $loadService)
    {
        $this->loadService = $loadService;
    }

    public function aroundGetValue(MinimalTierPriceCalculator $subject, callable $proceed, SaleableInterface $saleableItem)
    {
        $productId = (int)$saleableItem->getId();

        if ($saleableItem->getTypeId() === Configurable::TYPE_CODE
                && $this->loadService->has($productId, ConfigurableTierPrice::DATA_KEY)) {
            $tierPrice = ($this->loadService->get($productId, ConfigurableTierPrice::DATA_KEY)['tier_price'] ?? null);

            return $tierPrice ? (float)$tierPrice : null;
        }
        return $proceed($saleableItem);
    }
}
