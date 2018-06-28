<?php

namespace Drupal\commerce_promotion_api;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\commerce_promotion\PromotionUsageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class UserCouponManager.
 */
class UserCouponManager implements UserCouponManagerInterface
{
    /**
     * @var PromotionUsageInterface
     */
    protected $promotionUsage;

    /**
     * Constructs a new UserCouponManager object.
     * @param PromotionUsageInterface $promotion_usage
     */
    public function __construct(PromotionUsageInterface $promotion_usage)
    {
        $this->promotionUsage = $promotion_usage;
    }

    /**
     * @inheritdoc
     */
    public function userReceivedCoupon(AccountInterface $user, PromotionInterface $promotion)
    {
        foreach ($promotion->getCoupons() as $coupon) {
            if ($coupon->get('user_id')->entity->id() === $user->id()) return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getAvailableCoupon(PromotionInterface $promotion)
    {
        foreach ($promotion->getCoupons() as $coupon) {
            if ($coupon->get('user_id')->isEmpty() && $this->isAvailableCoupon($coupon)) return $coupon;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function isAvailableCoupon(CouponInterface $coupon)
    {
        if ($coupon->isEnabled() && $coupon->getUsageLimit() > $this->promotionUsage->loadByCoupon($coupon)) return true;
    }

    /**
     * @inheritdoc
     */
    public function cartCanUseCoupon(OrderInterface $cart, CouponInterface $coupon)
    {
        if ($cart->getCustomer()->id() === $coupon->get('user_id')->entity->id() && $this->isAvailableCoupon($coupon)) {
            // 不能重复使用同一个优惠券
            foreach ($cart->get('coupons') as $couponItem) {
                if ($coupon->id() === $couponItem->entity->id()) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }
}
