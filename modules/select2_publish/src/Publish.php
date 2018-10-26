<?php

namespace Drupal\select2_publish;

use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Attach status properties to the render element.
 */
class Publish {

  /**
   * Attach status properties to the render element.
   */
  public static function preRenderPublish($element) {
    if ($element['#target_type']) {
      $entities = \Drupal::entityTypeManager()
        ->getStorage($element['#target_type'])
        ->loadMultiple(array_keys($element['#options']));

      foreach ($entities as $id => $entity) {
        $properties = [];
        if ($entity instanceof EntityPublishedInterface) {
          $properties['data-published'] = $entity->isPublished() ? 'true' : 'false';
        }
        $element['#options_attributes'][$id] = $properties;
      }

      $default_status = 'true';
      if ($element['#autocreate']) {
        /** @var \Drupal\Core\Entity\EntityPublishedInterface $entity */
        $entity_definition = \Drupal::entityTypeManager()->getDefinition($element['#target_type']);
        $entity = \Drupal::entityTypeManager()->getStorage($element['#target_type'])->create([$entity_definition->getKey('bundle') => $element['#autocreate']['bundle']]);
        $default_status = $entity->isPublished() ? 'true' : 'false';
      }

      $element['#attached']['library'][] = 'select2_publish/select2.publish';
      $element['#attributes']['data-select2-publish-default'] = $default_status;
      return $element;
    }

    return $element;
  }

}
