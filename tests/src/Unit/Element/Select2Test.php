<?php

namespace Drupal\Tests\select2\Unit\Element;

use Drupal\Core\DependencyInjection\ContainerBuilder;
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

    $theme = $this->createMock('Drupal\Core\Theme\ActiveTheme');
    $theme->expects($this->any())
      ->method('getName')
      ->will($this->returnValue('seven'));

    $theme_manager = $this->createMock('Drupal\Core\Theme\ThemeManagerInterface');
    $theme_manager->expects($this->any())
      ->method('getActiveTheme')
      ->will($this->returnValue($theme));

    $library_discovery = $this->createMock('Drupal\Core\Asset\LibraryDiscoveryInterface');
    $library_discovery->expects($this->any())
      ->method('getLibraryByName')
      ->will($this->returnValue(TRUE));

    $container = new ContainerBuilder();
    $container->set('language_manager', $language_manager);
    $container->set('theme.manager', $theme_manager);
    $container->set('library.discovery', $library_discovery);

    \Drupal::setContainer($container);
  }

  /**
   * @covers ::preRenderSelect
   *
   * @dataProvider providerTestPreRenderSelect
   */
  public function testPreRenderSelect($multiple, $required, $settings, $expected) {
    $element = [
      '#name' => 'field_foo',
      '#options' => [],
      '#multiple' => $multiple,
      '#required' => $required,
      '#attributes' => ['data-drupal-selector' => 'field-foo'],
      '#autocreate' => [],
      '#autocomplete' => FALSE,
      '#cardinality' => 0,
      '#select2' => $settings,
    ];

    $element = Select2::preRenderSelect($element);
    $element = Select2::preRenderAutocomplete($element);
    $element = Select2::preRenderOverwrites($element);
    $this->assertArraySubset($expected, $element);
  }

  /**
   * Data provider for testPreRenderSelect().
   */
  public function providerTestPreRenderSelect() {
    $data = [];
    $data[] = [TRUE, TRUE, [],
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
    $data[] = [FALSE, TRUE, [],
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
    $data[] = [TRUE, FALSE, [],
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
    $data[] = [FALSE, FALSE, [],
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
    // Test overwriting of the default setting.
    $data[] = [FALSE, FALSE, ['allowClear' => FALSE, 'multiple' => TRUE],
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

    return $data;
  }

}
