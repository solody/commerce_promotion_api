<?php

namespace Drupal\commerce_promotion_api\Normalizer;

use Drupal\commerce\Plugin\Field\FieldType\PluginItemInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\serialization\Normalizer\FieldItemNormalizer;

/**
 * Converts values for TimestampItem to and from common formats.
 */
class CommercePluginItemNormalizer extends FieldItemNormalizer {

    /**
     * The interface or class that this Normalizer supports.
     *
     * @var string
     */
    protected $supportedInterfaceOrClass = PluginItemInterface::class;

    /**
     * {@inheritdoc}
     */
    public function normalize($field_item, $format = NULL, array $context = []) {
        $data = parent::normalize($field_item, $format, $context);
        $config_data = $field_item->get('target_plugin_configuration')->getValue();
        if (isset($config_data['products']) && !empty($config_data['products']) && is_array($config_data['products']) && count($config_data['products'])) {
            $products_data = [];
            foreach ($config_data['products'] as $productConfig) {
                $product = Product::load($productConfig['product_id']);
                $products_data[] = $this->serializer->normalize($product, $format, $context);
            }
            $data['_products'] = $products_data;
        }

        $data['target_plugin_configuration'] = $config_data;
        return $data;
    }
}