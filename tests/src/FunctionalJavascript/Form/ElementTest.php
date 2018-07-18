<?php

namespace Drupal\Tests\select2\FunctionalJavascript\Form;

use Drupal\Tests\select2\FunctionalJavascript\Select2JavascriptTestBase;

/**
 * Tests the select2 element.
 *
 * @group select2
 */
class ElementTest extends Select2JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['select2_form_test'];

  /**
   * Tests select2 optgroups.
   */
  public function testOptgroups() {
    $this->drupalGet('form-test-select2');

    $this->click('.form-item-select2-optgroups .select2-selection.select2-selection--single');

    $this->assertSession()->elementTextContains('css', '.select2-results__group', 'Baba');
    $this->assertSession()->elementTextContains('css', 'ul.select2-results__options li.select2-results__option ul.select2-results__options--nested li.select2-results__option', 'Nana');
  }

}
