<?php

namespace Drupal\Tests\select2\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Base class for Select2 module integration tests.
 */
abstract class Select2KernelTestBase extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['select2'];

  /**
   * The field name used in the test.
   *
   * @var string
   */
  protected $fieldName = 'test_select2';

  /**
   * The field storage definition used to created the field storage.
   *
   * @var array
   */
  protected $fieldStorageDefinition;

  /**
   * The list field storage used in the test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The list field used in the test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container->get('router.builder')->rebuild();

    $this->installEntitySchema('entity_test_mulrevpub');

    $entity_type = 'entity_test';
    $bundle = 'entity_test';
    $name = $this->fieldName;
    $field_type = 'entity_reference';
    $storage_settings = [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => -1,
    ];
    $field_settings = [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'target_bundles' => ['entity_test_mulrevpub' => 'entity_test_mulrevpub'],
        'auto_create' => TRUE,
      ],
    ];
    $widget_type = 'select2_entity_reference';
    $widget_settings = ['autocomplete' => TRUE];

    /* Create field. */
    $this->fieldStorageDefinition = [
      'entity_type' => $entity_type,
      'field_name' => $name,
      'type' => $field_type,
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ];
    $this->fieldStorage = FieldStorageConfig::create($this->fieldStorageDefinition);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    ]);
    $this->field->save();

    entity_get_form_display($entity_type, $bundle, 'default')
      ->setComponent($name, [
        'type' => $widget_type,
        'settings' => $widget_settings,
      ])
      ->save();
  }

}
