<?php

/**
 * @file
 * Here are post-update hooks for the select2 module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\select2\Plugin\Field\FieldWidget\Select2EntityReferenceWidget;

/**
 * Populate the new 'match_limit' setting for the select2 autocomplete widget.
 */
function select2_post_update_entity_reference_autocomplete_match_limit(&$sandbox = NULL) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  /** @var \Drupal\Core\Field\WidgetPluginManager $field_widget_manager */
  $field_widget_manager = \Drupal::service('plugin.manager.field.widget');

  $callback = function (EntityDisplayInterface $display) use ($field_widget_manager) {
    foreach ($display->getComponents() as $field_name => $component) {
      if (empty($component['type'])) {
        continue;
      }

      $plugin_definition = $field_widget_manager->getDefinition($component['type'], FALSE);
      if (is_a($plugin_definition['class'], Select2EntityReferenceWidget::class, TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
  };

  $config_entity_updater->update($sandbox, 'entity_form_display', $callback);
}
