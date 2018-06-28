<?php

namespace Drupal\commerce_promotion_api\Plugin\rest\resource;

use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "commerce_promotion_api_query_promotions",
 *   label = @Translation("Query promotions"),
 *   uri_paths = {
 *     "create" = "/api/rest/commerce-promotion/query-promotions"
 *   }
 * )
 */
class QueryPromotions extends ResourceBase
{

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * Constructs a new QueryPromotions object.
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
        AccountProxyInterface $current_user)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
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
            $container->get('current_user')
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

        $query = \Drupal::entityQuery('commerce_promotion');

        if (isset($data['has_coupon'])) {
            if ($data['has_coupon'] === true) {
                $query->exists('coupons');
            } else {
                $query->notExists('coupons');
            }
        }

        if (isset($data['condition_type'])) {
            $query->condition('conditions.target_plugin_id', $data['condition_type']);
        }

        $ids = $query->execute();

        $rs = [];
        if (!empty($ids)) $rs = Promotion::loadMultiple($ids);

        return new ModifiedResourceResponse(array_values($rs), 200);
    }

}
