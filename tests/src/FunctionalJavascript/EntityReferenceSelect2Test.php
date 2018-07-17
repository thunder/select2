<?php

namespace Drupal\Tests\select2\FunctionalJavascript;

use Drupal\entity_test\Entity\EntityTestMulRevPub;

/**
 * Tests select2 entity reference widget.
 *
 * @group select2
 */
class EntityReferenceSelect2Test extends Select2JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * Test autocomplete in a single value field.
   */
  public function testSingleAutocomplete() {
    $this->createField('select2', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'target_bundles' => ['entity_test_mulrevpub' => 'entity_test_mulrevpub'],
        'auto_create' => FALSE,
      ],
    ], 'select2_entity_reference', ['autocomplete' => TRUE]);

    EntityTestMulRevPub::create(['name' => 'foo'])->setPublished()->save();
    EntityTestMulRevPub::create(['name' => 'bar'])->save();
    EntityTestMulRevPub::create(['name' => 'gaga'])->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');
    $this->click('.form-item-select2 .select2-selection.select2-selection--single');

    $page->find('css', '.select2-search__field')->setValue('fo');
    $assert_session->waitForElement('xpath', '//li[@class="select2-results__option published select2-results__option--highlighted" and text()="foo"]');
    $page->find('xpath', '//li[@class="select2-results__option published select2-results__option--highlighted" and text()="foo"]')->click();
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);
    $this->assertArraySubset([['target_id' => 1]], $node->select2->getValue());
  }

  /**
   * Test autocomplete in a multiple value field.
   */
  public function testMultipleAutocomplete() {
    $this->createField('select2', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => -1,
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'target_bundles' => ['entity_test_mulrevpub' => 'entity_test_mulrevpub'],
        'auto_create' => FALSE,
      ],
    ], 'select2_entity_reference', ['autocomplete' => TRUE]);

    EntityTestMulRevPub::create(['name' => 'foo'])->setPublished()->save();
    EntityTestMulRevPub::create(['name' => 'bar'])->save();
    EntityTestMulRevPub::create(['name' => 'gaga'])->setUnpublished()->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');

    $this->click('.form-item-select2 .select2-selection.select2-selection--multiple');
    $page->find('css', '.select2-search__field')->setValue('fo');
    $assert_session->waitForElement('xpath', '//li[@class="select2-results__option published select2-results__option--highlighted" and text()="foo"]');
    $page->find('xpath', '//li[@class="select2-results__option published select2-results__option--highlighted" and text()="foo"]')->click();

    $this->click('.form-item-select2 .select2-selection.select2-selection--multiple');
    $page->find('css', '.select2-search__field')->setValue('ga');
    $assert_session->waitForElement('xpath', '//li[@class="select2-results__option unpublished select2-results__option--highlighted" and text()="gaga"]');
    $page->find('xpath', '//li[@class="select2-results__option unpublished select2-results__option--highlighted" and text()="gaga"]')->click();

    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);
    $this->assertArraySubset([['target_id' => 1], ['target_id' => 3]], $node->select2->getValue());
  }

  /**
   * Test autocreation for a single value field.
   */
  public function testSingleAutocreation() {
    $this->createField('select2', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => 1,
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'target_bundles' => ['entity_test_mulrevpub' => 'entity_test_mulrevpub'],
        'auto_create' => TRUE,
      ],
    ], 'select2_entity_reference');

    $page = $this->getSession()->getPage();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');

    $this->click('.form-item-select2 .select2-selection.select2-selection--single');
    $page->find('css', '.select2-search__field')->setValue('New value');
    $page->find('css', '.select2-results__option--highlighted')->click();
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);
    $this->assertArraySubset([['target_id' => 1]], $node->select2->getValue());
    $this->assertNotEmpty(EntityTestMulRevPub::load(1));
  }

  /**
   * Test autocreation for a multi value field.
   */
  public function testMultipleAutocreation() {
    $this->createField('select2', 'node', 'test', 'entity_reference', [
      'target_type' => 'entity_test_mulrevpub',
      'cardinality' => -1,
    ], [
      'handler' => 'default:entity_test_mulrevpub',
      'handler_settings' => [
        'target_bundles' => ['entity_test_mulrevpub' => 'entity_test_mulrevpub'],
        'auto_create' => TRUE,
      ],
    ], 'select2_entity_reference');

    $page = $this->getSession()->getPage();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');
    $this->click('.form-item-select2 .select2-selection.select2-selection--multiple');
    $page->find('css', '.select2-search__field')->setValue('New value 1');
    $page->find('css', '.select2-results__option--highlighted')->click();

    $this->click('.form-item-select2 .select2-selection.select2-selection--multiple');
    $page->find('css', '.select2-search__field')->setValue('New value 2');
    $page->find('css', '.select2-results__option--highlighted')->click();

    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);
    $this->assertArraySubset([['target_id' => 1], ['target_id' => 2]], $node->select2->getValue());
    $this->assertNotEmpty(EntityTestMulRevPub::load(1));
    $this->assertNotEmpty(EntityTestMulRevPub::load(2));
  }

}
