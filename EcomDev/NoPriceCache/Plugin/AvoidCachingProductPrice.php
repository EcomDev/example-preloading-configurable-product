<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace EcomDev\NoPriceCache\Plugin;

use Magento\Framework\Pricing\Render\PriceBox;

/**
 * AvoidCachingProductPrice
 */
class AvoidCachingProductPrice
{
    /**
     * Prevents caching pricebox by setting lifetime to null
     */
    public function beforeToHtml(\Magento\Framework\Pricing\Render\PriceBox $subject)
    {
        $subject->setData('cache_lifetime', false);
        return [];
    }

}
