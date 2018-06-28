<?php

namespace Drupal\commerce_promotion_api\Plugin\rest\resource;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion_api\UserCouponManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "commerce_promotion_api_coupon_redemption",
 *   label = @Translation("Coupon redemption"),
 *   uri_paths = {
 *     "create" = "/api/rest/commerce-promotion/coupon-redemption"
 *   }
 * )
 */
class CouponRedemption extends ResourceBase
{

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * @var UserCouponManagerInterface
     */
    protected $userCouponManager;

    /**
     * Constructs a new CouponRedemption object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user,
        UserCouponManagerInterface $user_coupon_manager)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
        $this->userCouponManager = $user_coupon_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('commerce_promotion_api'),
            $container->get('current_user'),
            $container->get('commerce_promotion_api.user_coupon_manager')
        );
    }

    /**
     * Responds to POST requests.
     *
     * @param $data
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     */
    public function post($data)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $cart = Order::load($data['cart']);
        $coupon = Coupon::load($data['coupon']);

        if ($cart && $cart->get('cart')->value && $coupon) {
            if ($this->userCouponManager->cartCanUseCoupon($cart, $coupon)) {
                try {
                    $cart->get('coupons')->appendItem($coupon);
                    $cart->save();
                } catch (\Exception $exception) {
                    throw new BadRequestHttpException('处理数据出错' . $exception->getMessage());
                }
            } else {
                throw new BadRequestHttpException('此优惠券不能在此处使用');
            }
        } else {
            throw new BadRequestHttpException('无效的购物车订单或优惠券');
        }

        return new ModifiedResourceResponse($data, 200);
    }

}
