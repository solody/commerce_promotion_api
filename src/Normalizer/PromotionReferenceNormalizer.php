<?php

namespace Drupal\commerce_promotion_api\Normalizer;

use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;

/**
 * Expands order items to their referenced entity.
 */
class PromotionReferenceNormalizer extends EntityReferenceFieldItemNormalizer {

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = NULL) {
        $supported = parent::supportsNormalization($data, $format);
        if ($data instanceof EntityReferenceItem) {
            $entity = $data->get('entity')->getValue();
            if ($entity instanceof PromotionInterface) return true;
        }
        return FALSE;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($field_item, $format = NULL, array $context = []) {
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $field_item */
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        if ($entity = $field_item->get('entity')->getValue()) {
            return $this->serializer->normalize($entity, $format, $context);
        }
        return $this->serializer->normalize([], $format, $context);
    }

}
