<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace EcomDev\ConfigurableProductPreloader\Plugin;

use EcomDev\ConfigurableProductPreloader\Loader\ConfigurableSalable;
use EcomDev\ProductDataPreLoader\DataService\LoadService;
use Magento\Catalog\Model\Product\Pricing\Renderer\SalableResolver;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Pricing\SaleableInterface;

class ConfigurableSalableCheckPlugin
{
    private LoadService $loadService;

    public function __construct(LoadService $loadService)
    {
        $this->loadService = $loadService;
    }

    public function afterIsSalable(SalableResolver $subject, $result, SaleableInterface $salableItem)
    {
        $productId = (int)$salableItem->getId();
        $canProcess = $salableItem->getTypeId() === Configurable::TYPE_CODE && $result;
        if ($canProcess && $this->loadService->has($productId, ConfigurableSalable::DATA_KEY)) {
            return (bool)($this->loadService->get($productId, ConfigurableSalable::DATA_KEY)['is_salable'] ?? false);
        }
        return $result;
    }
}
