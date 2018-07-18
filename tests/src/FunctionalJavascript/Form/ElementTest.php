<?php

namespace Drupal\Tests\select2\FunctionalJavascript\Form;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the select2 element.
 *
 * @group select2
 */
class ElementTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['select2_form_test'];

  /**
   * Tests select2 optgroups.
   */
  public function testOptgroups() {
    $this->drupalGet('/select2-optgroup-form');

    $this->click('.form-item-select2-optgroups .select2-selection.select2-selection--single');

    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.select2-results__group'));

    $this->assertSession()->elementTextContains('css', '.select2-results__group', 'Baba');
    $this->assertSession()->elementTextContains('css', 'ul.select2-results__options li.select2-results__option ul.select2-results__options--nested li.select2-results__option', 'Nana');
  }

}
