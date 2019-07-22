<?php

namespace Drupal\Tests\select2\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRevPub;

/**
 * Class Select2ValidOptionsTest.
 */
class Select2ValidOptionsTest extends Select2KernelTestBase {

  /**
   * Tests that available options are set according to values..
   */
  public function testAvailableOptions() {
    $entity = EntityTest::create();
    $ref1 = EntityTestMulRevPub::create(['name' => 'Drupal Temp']);
    $ref2 = EntityTestMulRevPub::create(['name' => 'Test']);
    $ref1->save();
    $ref2->save();

    // Create a new revision to trigger problem.
    $ref1->setName('Drupal')->setNewRevision();
    $ref1->save();

    $entity->{$this->fieldName}->setValue([['target_id' => $ref1->id()], ['target_id' => $ref2->id()]]);
    $entity->save();

    $form = \Drupal::service('entity.form_builder')->getForm($entity);
    $this->assertTrue($form[$this->fieldName]['widget']['#options'] === [$ref1->id() => $ref1->getName(), $ref2->id() => $ref2->getName()], 'Option values differ from expected values.');
  }

}
