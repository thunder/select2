<?php

namespace Drupal\select2;

use Drupal\Component\Utility\Html;

/**
 * Trait with functions that are used in the element and the field widget.
 */
trait Select2Trait {

  /**
   * Validates an array of IDs.
   *
   * @param array $ids
   *   Array of entity IDs.
   * @param array $handler_settings
   *   Handler settings to load a selection plugin.
   *
   * @return array
   *   Key => entity ID, Value => entity label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getValidReferenceableEntities(array $ids, array $handler_settings) {
    $options = [];
    $valid_ids = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($handler_settings)->validateReferenceableEntities($ids);
    $entities = \Drupal::entityTypeManager()->getStorage($handler_settings['target_type'])->loadMultiple($valid_ids);
    $display = \Drupal::entityTypeManager()
      ->getStorage('view')
      ->load($handler_settings['view']['view_name'])
      ->getDisplay($handler_settings['view']['display_name']);

    $fields = $display['display_options']['row']['options']['inline'];
    $seperator = $display['display_options']['row']['options']['separator'];
    foreach ($entities as $entity_id => $entity) {
      $context_entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity);
      $output = "";
      foreach($fields as $field){
        if($context_entity->hasField($field)){
          $output .= "{$context_entity->get($field)->getString()}{$seperator}";
        }
      }
      $output = rtrim($output, $seperator);
      $options[$entity_id] = Html::decodeEntities($output);
    }
    return $options;
  }
}
