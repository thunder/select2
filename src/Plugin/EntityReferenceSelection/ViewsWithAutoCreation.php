<?php

namespace Drupal\select2\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;

/**
 * Plugin implementation of the 'selection' entity_reference with autocreation.
 *
 * @EntityReferenceSelection(
 *   id = "views_with_autocreation",
 *   label = @Translation("Views: Filter by an entity reference view and allow autocreation"),
 *   group = "views",
 *   weight = 0
 * )
 */
class ViewsWithAutoCreation extends ViewsSelection implements SelectionWithAutocreateInterface {

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_key = $entity_type->getKey('bundle');
    $label_key = $entity_type->getKey('label');
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->create([
      $bundle_key => $bundle,
      $label_key => $label,
    ]);
    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId($uid);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    return array_filter($entities, function ($entity) {
      if (isset($this->configuration['handler_settings']['auto_create_bundle'])) {
        return ($entity->bundle() === $this->configuration['handler_settings']['auto_create_bundle']);
      }
      return TRUE;
    });
  }

}
