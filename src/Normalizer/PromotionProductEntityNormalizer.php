<?php

namespace Drupal\commerce_promotion_api\Normalizer;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_promotion\PromotionUsageInterface;
use Drupal\commerce_promotion_api\ProductPromotionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\serialization\Normalizer\EntityNormalizer;

/**
 * 如果一个产品有促销数据，添加到接口
 */
class PromotionProductEntityNormalizer extends EntityNormalizer {

    /**
     * @var ProductPromotionInterface
     */
    protected $productPromotion;

    /**
     * @var PromotionUsageInterface
     */
    protected $usage;

    /**
     * The interface or class that this Normalizer supports.
     *
     * @var string
     */
    protected $supportedInterfaceOrClass = Product::class;

    public function __construct(EntityManagerInterface $entity_manager, ProductPromotionInterface $product_promotion, PromotionUsageInterface $usage) {
        parent::__construct($entity_manager);
        $this->productPromotion = $product_promotion;
        $this->usage = $usage;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($entity, $format = NULL, array $context = []) {
        $data = parent::normalize($entity, $format, $context);
        // 查找promotion
        $promotions = $this->productPromotion->getProductPromotions($entity);
        $data['_promotions'] = [];
        foreach ($promotions as $promotion) {
            $data['_promotions'][] = [
                'promotion_id' => $promotion->id(),
                'name' => $promotion->getName(),
                'usage_limit' => $promotion->getUsageLimit(),
                'usage' => $this->usage->load($promotion),
                'start_time' => $promotion->get('start_date')->value,
                'end_time' => $promotion->get('end_date')->value
            ];
        }
        return $data;
    }
}