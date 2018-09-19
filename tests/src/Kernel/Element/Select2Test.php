<?php

namespace Drupal\Tests\select2\Kernel\Element;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\select2\Element\Select2
 *
 * @group select2
 */
class Select2Test extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'select2'];

  /**
   * @covers ::preRenderSelect
   * @covers ::preRenderAutocomplete
   */
  public function testSelect2Theming() {
    $select = [
      '#type' => 'select2',
      '#options' => [],
      '#required' => FALSE,
      '#attributes' => ['data-drupal-selector' => 'field-foo'],
    ];

    $this->render($select);
    $select2_js = $this->xpath("//script[contains(@src, 'select2/js/select2.js')]");
    $this->assertEquals(1, count($select2_js));
    $select2_js = $this->xpath("//script[contains(@src, 'select2/dist/js/select2.min.js')]");
    $this->assertEquals(1, count($select2_js));
  }

  /**
   * Tests that an empty option is added or not.
   */
  public function testEmptyOption() {
    $select = [
      '#type' => 'select2',
      '#options' => [],
      '#multiple' => FALSE,
      '#required' => FALSE,
      '#attributes' => ['data-drupal-selector' => 'field-foo'],
    ];
    $this->render($select);
    $select = $this->xpath('//select[@data-drupal-selector="field-foo"]/option[@value=""]');
    $this->assertEquals(1, count($select));

    $select = [
      '#type' => 'select2',
      '#options' => [],
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#attributes' => ['data-drupal-selector' => 'field-foo'],
      '#name' => 'field_foo',
    ];
    $this->render($select);
    $select = $this->xpath('//select[@data-drupal-selector="field-foo"]/option[@value=""]');
    $this->assertEquals(0, count($select));
  }

}
