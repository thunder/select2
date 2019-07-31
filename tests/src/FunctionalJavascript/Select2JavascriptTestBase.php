<?php

namespace Drupal\Tests\select2\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\select2\Traits\Select2TestTrait;

/**
 * Class Select2JavascriptTestBase.
 *
 * Base class for select2 Javascript tests.
 */
abstract class Select2JavascriptTestBase extends WebDriverTestBase {

  use Select2TestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'select2', 'options'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'test']);

    $user = $this->drupalCreateUser([
      'access content',
      'edit own test content',
      'create test content',
    ]);

    $this->drupalLogin($user);
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Can be removed with 8.6 support.
   */
  protected function initFrontPage() {
    parent::initFrontPage();
    // Set a standard window size so that all javascript tests start with the
    // same viewport.
    $this->getSession()->resizeWindow(1024, 768);
  }

  /**
   * Selects an option in a select2 widget.
   *
   * @param string $field
   *   Name of the field.
   * @param array $keys
   *   Values for the field.
   */
  protected function selectOption($field, array $keys) {
    $this->getSession()->executeScript("jQuery('#$field').val(['" . implode("', '", $keys) . "'])");
    $this->getSession()->executeScript("jQuery('#$field').trigger('change')");
  }

  /**
   * Scroll element with defined css selector in middle of browser view.
   *
   * @param string $cssSelector
   *   CSS Selector for element that should be centralized.
   */
  protected function scrollElementInView($cssSelector) {
    $this->getSession()
      ->executeScript('
        var viewPortHeight = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
        var element = jQuery(\'' . addcslashes($cssSelector, '\'') . '\');
        var scrollTop = element.offset().top - (viewPortHeight/2);
        var scrollableParent = jQuery.isFunction(element.scrollParent) ? element.scrollParent() : [];
        if (scrollableParent.length > 0 && scrollableParent[0] !== document && scrollableParent[0] !== document.body) { scrollableParent[0].scrollTop = scrollTop } else { window.scroll(0, scrollTop); };
      ');
  }

}
