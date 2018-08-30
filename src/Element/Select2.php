<?php

namespace Drupal\select2\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides a form element for a simple select2 select box.
 *
 * @FormElement("select2")
 */
class Select2 extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // Apply default form element properties.
    $info['#target_type'] = NULL;
    $info['#selection_handler'] = 'default';
    $info['#selection_settings'] = [];
    $info['#autocomplete'] = FALSE;
    $info['#autocreate'] = FALSE;
    $info['#cardinality'] = 0;
    $info['#pre_render'][] = [$class, 'preRenderAutocomplete'];
    $info['#element_validate'][] = [$class, 'validateElement'];
    $info['#select2'] = [];

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#multiple']) {
      $element['#attributes']['multiple'] = 'multiple';
      $element['#attributes']['name'] = $element['#name'] . '[]';
      // Ensure that we don't have an empty value for multiple selection.
      unset($element['#options']['']);
    }
    else {
      $empty_option = ['' => ''];
      $element['#options'] = $empty_option + $element['#options'];
    }

    // We need to disable form validation, because with autocreation the options
    // could contain non existing references. We still have validation in the
    // entity reference field.
    if ($element['#autocreate'] && $element['#target_type']) {
      unset($element['#needs_validation']);
    }

    // Set the type from select2 to select to get proper form validation.
    $element['#type'] = 'select';

    return $element;
  }

  /**
   * Create a new entity.
   *
   * @param array $element
   *   The form element.
   * @param string $input
   *   The input for the new entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new unsaved entity.
   */
  protected static function createNewEntity(array $element, $input) {
    $options = $element['#selection_settings'] + [
      'target_type' => $element['#target_type'],
      'handler' => $element['#selection_handler'],
    ];
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
    if (!$handler instanceof SelectionWithAutocreateInterface) {
      return NULL;
    }

    $label = substr($input, 4);
    $bundle = reset($element['#selection_settings']['target_bundles']);
    // We are not saving created entities, because that's part of
    // Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::preSave().
    return $handler->createNewEntity($element['#target_type'], $bundle, $label, \Drupal::currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderSelect($element) {
    $element = parent::preRenderSelect($element);
    $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
    $multiple = $element['#multiple'];

    if ($element['#autocomplete'] && $element['#target_type']) {
      // Reduce options to the preselected ones and bring them in the correct
      // order.
      $options = [];
      foreach ($element['#default_value'] as $value) {
        $options[$value] = $element['#options'][$value];
      }
      $element['#options'] = $options;

      if (!$multiple) {
        $empty_option = ['' => ''];
        $element['#options'] = $empty_option + $element['#options'];
      }
    }

    $current_language = \Drupal::languageManager()->getCurrentLanguage();
    $current_theme = \Drupal::theme()->getActiveTheme()->getName();
    $select2_theme_exists = \Drupal::service('library.discovery')->getLibraryByName($current_theme, 'select2.theme');
    // Defining the select2 configuration.
    $settings = [
      'multiple' => $multiple,
      'placeholder' => $required ? new TranslatableMarkup('- Select -') : new TranslatableMarkup('- None -'),
      // @TODO: Enable allowClear for multiple fields. https://github.com/select2/select2/issues/3335.
      'allowClear' => !$multiple && !$required,
      'dir' => $current_language->getDirection(),
      'language' => $current_language->getId(),
      'tags' => $element['#autocreate'],
      'theme' => $select2_theme_exists ? $current_theme : 'default',
      'maximumSelectionLength' => $multiple ? $element['#cardinality'] : 0,
      'tokenSeparators' => $element['#autocreate'] ? [','] : [],
      'selectOnClose' => $element['#autocomplete'],
    ];

    // Allow to overwrite the default settings and set additional settings.
    foreach ($element["#select2"] as $key => $value) {
      $settings[$key] = $value;
    }

    $selector = $element['#attributes']['data-drupal-selector'];
    $element['#attributes']['class'][] = 'select2-widget';
    $element['#attached']['drupalSettings']['select2'][$selector] = $settings;

    // Adding the select2 library.
    $element['#attached']['library'][] = 'select2/select2';
    $element['#attached']['library'][] = 'select2/select2.i18n.' . $current_language->getId();
    if ($select2_theme_exists) {
      $element['#attached']['library'][] = $current_theme . '/select2.theme';
    }
    return $element;
  }

  /**
   * Attach autocomplete behavior to the render element.
   */
  public static function preRenderAutocomplete($element) {
    if (!$element['#autocomplete']) {
      return $element;
    }

    // Nothing to do if there is no target entity type.
    if (empty($element['#target_type'])) {
      throw new \InvalidArgumentException('Missing required #target_type parameter.');
    }

    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $selection_settings = isset($element['#selection_settings']) ? $element['#selection_settings'] : [];
    $data = serialize($selection_settings) . $element['#target_type'] . $element['#selection_handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $element['#autocomplete_route_name'] = 'select2.entity_autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'target_type' => $element['#target_type'],
      'selection_handler' => $element['#selection_handler'],
      'selection_settings_key' => $selection_settings_key,
    ];

    /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
    $access_manager = \Drupal::service('access_manager');
    $access = $access_manager->checkNamedRoute($element['#autocomplete_route_name'], $element['#autocomplete_route_parameters'], \Drupal::currentUser(), TRUE);

    if ($access && $access->isAllowed()) {
      $url = Url::fromRoute($element['#autocomplete_route_name'], $element['#autocomplete_route_parameters'])
        ->toString(TRUE);

      // Provide a data attribute for the JavaScript behavior to bind to.
      $selector = $element['#attributes']['data-drupal-selector'];
      $element['#attached']['drupalSettings']['select2'][$selector] += [
        'minimumInputLength' => 1,
        'ajax' => [
          'url' => $url->getGeneratedUrl(),
        ],
      ];
    }
    return $element;
  }

  /**
   * Validates the select2 element.
   *
   * More or less a copy of OptionsWidgetBase::validateElement(). Changes are
   * '_none' was replaced by '' and we create new entities for non-existing
   * options.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '') {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Massage submitted form values.
    // Drupal\Core\Field\WidgetBase::submit() expects values as
    // an array of values keyed by delta first, then by column, while our
    // widgets return the opposite.
    if (is_array($element['#value'])) {
      $values = array_values($element['#value']);
    }
    else {
      $values = [$element['#value']];
    }

    // Filter out the '' option. Use a strict comparison, because
    // 0 == 'any string'.
    $index = array_search('', $values, TRUE);
    if ($index !== FALSE) {
      unset($values[$index]);
    }

    // Transpose selections from field => delta to delta => field.
    $items = [];
    // Options might be nested ("optgroups"), flatten the list.
    $options = OptGroup::flattenOptions($element['#options']);
    foreach ($values as $value) {
      if (isset($options[$value])) {
        $items[] = [$element['#key_column'] => $value];
      }
      else {
        $items[] = ['entity' => static::createNewEntity($element, $value)];
      }
    }
    $form_state->setValueForElement($element, $items);
  }

}
