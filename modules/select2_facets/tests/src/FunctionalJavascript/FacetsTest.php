<?php

namespace Drupal\Tests\select2_facets\FunctionalJavascript;

use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the select2 element.
 *
 * @group select2
 */
class FacetsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['select2_facets_test'];

  /**
   * Tests select2 autocomplete.
   */
  public function testAutocomplete() {
    $reference1 = EntityTestMulRevPub::create(['name' => 'Reference 1']);
    $reference1->save();
    $reference2 = EntityTestMulRevPub::create(['name' => 'Reference 2']);
    $reference2->save();
    $reference3 = EntityTestMulRevPub::create(['name' => 'Reference 3']);
    $reference3->save();
    EntityTestMulRevPub::create([
      'name' => 'Entity 1',
      'field_references' => [$reference1, $reference2],
    ])->save();
    EntityTestMulRevPub::create([
      'name' => 'Entity 2',
      'field_references' => [$reference1, $reference3],
    ])->save();

    $account = $this->createUser(['view test entity']);
    $this->drupalLogin($account);

    // Index all entities.
    search_api_cron();

    $this->drupalPlaceBlock('facet_block:referenced');

    $this->drupalGet('/test-entity-view');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->click('.form-item-referenced .select2-selection.select2-selection--multiple');
    $page->find('css', '.select2-search__field')->setValue('Reference 2');
    $page->find('xpath', '//li[@class="select2-results__option select2-results__option--highlighted"]')->click();

    $assert_session->addressEquals('test-entity-view?f%5B0%5D=referenced%3A2');

    $this->click('.form-item-referenced .select2-selection.select2-selection--multiple');
    $page->find('css', '.select2-search__field')->setValue('Reference 1');
    $page->find('xpath', '//li[@class="select2-results__option select2-results__option--highlighted"]')->click();

    $assert_session->addressEquals('test-entity-view?f%5B0%5D=referenced%3A2&f%5B1%5D=referenced%3A1');
  }

}
