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
    $reference1 = EntityTestMulRevPub::create(['name' => 'Reference 1'])->save();
    $reference2 = EntityTestMulRevPub::create(['name' => 'Reference 2'])->save();
    $reference3 = EntityTestMulRevPub::create(['name' => 'Reference 3'])->save();
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
  }

}
