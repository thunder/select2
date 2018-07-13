<?php

namespace Drupal\Tests\select2\FunctionalJavascript;

class SimpleSelect2Test extends Select2JavascriptTestBase {

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
   * Test single field selection.
   */
  function testSingleSelect() {
    $this->createField('select2', 'node', 'test', 'list_string', [
      'allowed_values' => [
        'foo' => 'Foo',
        'bar' => 'Bar',
      ],
    ], [], 'select2', []);

    $page = $this->getSession()->getPage();

    $this->drupalGet('/node/add/test');
    $page->fillField('title[0][value]', 'Test node');
    $this->selectOption('edit-select2', 'foo');
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node');
    $this->assertSame('foo', $node->select2->value);

    $this->drupalGet($node->toUrl('edit-form'));
    $this->click('.select2-selection__clear');
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);
    $this->assertSame(NULL, $node->select2->value);

    $this->drupalGet($node->toUrl('edit-form'));
    $this->selectOption('edit-select2', 'bar');
    $page->pressButton('Save');

    $node = $this->getNodeByTitle('Test node', TRUE);
    $this->assertSame('bar', $node->select2->value);
  }

}
