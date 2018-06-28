<?php

namespace Drupal\commerce_promotion_api\Plugin\rest\resource;

use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_promotion_api\UserCouponManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "commerce_promotion_api_receive_coupon",
 *   label = @Translation("Receive coupon"),
 *   uri_paths = {
 *     "create" = "/api/rest/commerce-promotion/receive-coupon"
 *   }
 * )
 */
class ReceiveCoupon extends ResourceBase
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
     * Constructs a new ReceiveCoupon object.
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
     *
     */
    public function post($data)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        // 用户领取优惠券
        $user = User::load($data['user']);
        $promotion = Promotion::load($data['promotion']);

        if ($user && $promotion) {
            // 检查用户是否已经领取过优惠券
            if ($this->userCouponManager->userReceivedCoupon($user, $promotion)) {
                throw new BadRequestHttpException('已经领取过此优惠券');
            }

            // 检查是否还有限额可领取
            $availableCoupon = $this->userCouponManager->getAvailableCoupon($promotion);
            if (!$availableCoupon) {
                throw new BadRequestHttpException('优惠券已经被领完');
            }

            try {
                $availableCoupon->set('user_id', $user->id());
                $availableCoupon->save();

                return new ModifiedResourceResponse($availableCoupon, 200);
            } catch (\Exception $e) {
                throw new BadRequestHttpException('处理数据出错，领取失败');
            }
        } else {
            throw new BadRequestHttpException('用户或优惠券不存在');
        }
    }

}
