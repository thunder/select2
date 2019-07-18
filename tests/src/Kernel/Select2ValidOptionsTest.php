<?php

namespace Drupal\Tests\select2\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRevPub;



class Select2ValidOptionsTest extends Select2KernelTestBase {

  /**
   * Tests that available options are set accorging to values..
   */
  public function testAvailableOptions() {
    $entity = EntityTest::create();
    $ref1 = EntityTestMulRevPub::create(['name' => 'Drupal']);
    $ref2 = EntityTestMulRevPub::create(['name' => 'Test']);
    $ref1->save();
    $ref2->save();

    $entity->{$this->fieldName}->setValue([['target_id' => $ref1->id()], ['target_id' => $ref2->id()]]);
    $entity->save();

    $form = \Drupal::service('entity.form_builder')->getForm($entity);
    $this->assertTrue($form[$this->fieldName]['widget']['#options'] === [$ref1->id() => $ref1->getName(), $ref2->id() => $ref2->getName()], 'Option values differ from expected values.');
  }

}
