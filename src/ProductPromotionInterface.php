<?php

namespace Drupal\commerce_promotion_api;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;

/**
 * Interface ProductPromotionInterface.
 */
interface ProductPromotionInterface
{
    /**
     * @param ProductInterface $product
     * @return PromotionInterface[]
     */
    public function getProductPromotions(ProductInterface $product);
}
