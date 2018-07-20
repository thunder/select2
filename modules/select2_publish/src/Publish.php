<?php

namespace Drupal\select2_publish;

use Drupal\Core\Entity\EntityPublishedInterface;

class Publish {

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


        $selector = $element['#attributes']['data-drupal-selector'];
        $element['#attached']['drupalSettings']['select2'][$selector];
        $element['#attached']['library'][] = 'select2_publish/select2.publish';


      }
    }

    return $element;
  }

}
