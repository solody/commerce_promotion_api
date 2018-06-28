<?php

namespace Drupal\commerce_promotion_api;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface UserCouponManagerInterface.
 */
interface UserCouponManagerInterface
{
    /**
     * @param AccountInterface $user
     * @param PromotionInterface $promotion
     * @return boolean
     */
    public function userReceivedCoupon(AccountInterface $user, PromotionInterface $promotion);

    /**
     * @param PromotionInterface $promotion
     * @return \Drupal\commerce_promotion\Entity\CouponInterface|null
     */
    public function getAvailableCoupon(PromotionInterface $promotion);

    /**
     * @param CouponInterface $coupon
     * @return bool
     */
    public function isAvailableCoupon(CouponInterface $coupon);

    /**
     * @param OrderInterface $cart
     * @param CouponInterface $coupon
     * @return bool
     */
    public function cartCanUseCoupon(OrderInterface $cart, CouponInterface $coupon);
}
