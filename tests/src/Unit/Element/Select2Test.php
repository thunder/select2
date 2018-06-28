<?php

namespace Drupal\Tests\select2\Unit\Element;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\select2\Element\Select2;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\select2\Element\Select2
 * @group select2
 */
class Select2Test extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $language = $this->createMock('Drupal\Core\Language\LanguageInterface');
    $language->expects($this->any())
      ->method('getDirection')
      ->will($this->returnValue('rtl'));
    $language->method('getId')
      ->will($this->returnValue('en'));

    $language_manager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));

    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager);

    \Drupal::setContainer($container);
  }

  /**
   * @covers ::processSelect
   *
   * @dataProvider providerTestProcessSelect
   */
  public function testProcessSelect($multiple, $required, $expected) {
    $element = [
      '#name' => 'field_foo',
      '#options' => [],
      '#multiple' => $multiple,
      '#required' => $required,
      '#attributes' => ['data-drupal-selector' => 'field-foo'],
    ];
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $complete_form = [];

    $this->assertArraySubset($expected, Select2::processSelect($element, $form_state, $complete_form));
  }

  /**
   * Data provider for testProcessSelect().
   */
  public function providerTestProcessSelect() {
    $data = [];
    $data[] = [TRUE, TRUE,
      [
        '#attributes' => ['multiple' => 'multiple', 'name' => 'field_foo[]'],
        '#attached' => [
          'drupalSettings' => [
            'select2' => [
              'field-foo' => [
                'multiple' => TRUE,
                'allowClear' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
    $data[] = [FALSE, TRUE,
      [
        '#attributes' => [],
        '#attached' => [
          'drupalSettings' => [
            'select2' => [
              'field-foo' => [
                'multiple' => FALSE,
                'allowClear' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
    $data[] = [TRUE, FALSE,
      [
        '#attributes' => [],
        '#attached' => [
          'drupalSettings' => [
            'select2' => [
              'field-foo' => [
                'multiple' => TRUE,
                'allowClear' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
    $data[] = [FALSE, FALSE,
      [
        '#attributes' => [],
        '#attached' => [
          'drupalSettings' => [
            'select2' => [
              'field-foo' => [
                'multiple' => FALSE,
                'allowClear' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];

    return $data;
  }

}
