<?php

namespace Drupal\commerce_promotion_api;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\commerce_promotion\PromotionUsageInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Class ProductPromotion.
 */
class ProductPromotion implements ProductPromotionInterface
{
    /**
     * The usage.
     *
     * @var \Drupal\commerce_promotion\PromotionUsageInterface
     */
    protected $usage;

    /**
     * The time.
     *
     * @var \Drupal\Component\Datetime\TimeInterface
     */
    protected $time;

    /**
     * Constructs a new ProductPromotion object.
     * @param PromotionUsageInterface $usage
     * @param TimeInterface $time
     */
    public function __construct(PromotionUsageInterface $usage, TimeInterface $time)
    {
        $this->usage = $usage;
        $this->time = $time;
    }

    /**
     * @inheritdoc
     */
    public function getProductPromotions(ProductInterface $product)
    {
        $promotions = $this->getAvailableNonCouponPromotions();

        $product_promotions = [];
        foreach ($promotions as $promotion) {
            if ($this->checkCanApplyForProduct($promotion, $product)) $product_promotions[] = $promotion;
        }

        return $product_promotions;
    }

    public function checkCanApplyForProduct(PromotionInterface $promotion, ProductInterface $product)
    {
        // 代码由Drupal\commerce_promotion\Entity\Promotion::applies修改而来
        $conditions = $promotion->getConditions();
        if (!$conditions) {
            // Promotions without conditions always apply.
            return TRUE;
        }

        $order_item_product_conditions = array_filter($conditions, function ($condition) {
            /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
            return $condition->getPluginId() == 'order_item_product';
        });

        foreach ($order_item_product_conditions as $condition) {
            $product_ids = array_column($condition->getConfiguration()['products'], 'product_id');
            if (in_array($product->id(), $product_ids)) return true;
            else continue;
        }

        return FALSE;
    }

    public function getAvailableNonCouponPromotions()
    {
        // 代码由Drupal\commerce_promotion\PromotionStorage::loadAvailable修改而来
        $today = gmdate('Y-m-d', $this->time->getRequestTime());
        $query = \Drupal::entityQuery('commerce_promotion');
        $or_condition = $query->orConditionGroup()
            ->condition('end_date', $today, '>=')
            ->notExists('end_date');
        $query
            ->condition('start_date', $today, '<=')
            ->condition('status', TRUE)
            ->condition($or_condition);
        // Only load promotions without coupons. Promotions with coupons are loaded
        // coupon-first in a different process.
        $query->notExists('coupons');
        $result = $query->execute();
        if (empty($result)) {
            return [];
        }

        $promotions = Promotion::loadMultiple($result);
        // Remove any promotions that have hit their usage limit.
        $promotions_with_usage_limits = array_filter($promotions, function ($promotion) {
            /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
            return !empty($promotion->getUsageLimit());
        });
        $usages = $this->usage->loadMultiple($promotions_with_usage_limits);
        foreach ($promotions_with_usage_limits as $promotion_id => $promotion) {
            /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $promotion */
            if ($promotion->getUsageLimit() <= $usages[$promotion_id]) {
                unset($promotions[$promotion_id]);
            }
        }
        // Sort the remaining promotions.
        uasort($promotions, [Promotion::class , 'sort']);

        return $promotions;
    }
}
