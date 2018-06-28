<?php

namespace Drupal\commerce_promotion_api\Normalizer;

use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\commerce_promotion\PromotionUsageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\serialization\Normalizer\EntityNormalizer;

/**
 * 为促销活动添加已使用数量
 */
class PromotionEntityNormalizer extends EntityNormalizer {

    /**
     * @var PromotionUsageInterface
     */
    protected $usage;

    /**
     * The interface or class that this Normalizer supports.
     *
     * @var string
     */
    protected $supportedInterfaceOrClass = PromotionInterface::class;

    public function __construct(EntityManagerInterface $entity_manager, PromotionUsageInterface $usage) {
        parent::__construct($entity_manager);
        $this->usage = $usage;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($entity, $format = NULL, array $context = []) {
        $data = parent::normalize($entity, $format, $context);
        $data['_usage'] = $this->usage->load($entity);
        return $data;
    }
}