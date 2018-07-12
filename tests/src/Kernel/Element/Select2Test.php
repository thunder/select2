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
    $select2_js = $this->xpath("//script[contains(@src, 'modules/select2/js/select2.js')]");
    $this->assertEqual(count($select2_js), 1);
    $select2_js = $this->xpath("//script[contains(@src, 'select2/dist/js/select2.min.js')]");
    $this->assertEqual(count($select2_js), 1);
    $select2_css = $this->xpath("//link[contains(@href, 'modules/select2/css/select2.seven.css')]");
    $this->assertEqual(count($select2_css), 1);
    $select2_css = $this->xpath("//link[contains(@href, 'modules/select2/css/select2.seven-overrides.css')]");
    $this->assertEqual(count($select2_css), 1);
  }

}
