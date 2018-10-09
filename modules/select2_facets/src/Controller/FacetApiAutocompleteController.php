<?php

namespace Drupal\select2_facets\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\facets\Processor\ProcessorInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a route controller for facets autocomplete form elements.
 */
class FacetApiAutocompleteController extends ControllerBase {

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The facet manager service.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * Constructs a FacetApiAutocompleteController object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value
   *   The key value factory.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facetManager
   *   The facet manager service.
   */
  public function __construct(KeyValueStoreInterface $key_value, DefaultFacetManager $facetManager) {
    $this->keyValue = $key_value;
    $this->facetManager = $facetManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue')->get('entity_autocomplete'),
      $container->get('keyvalue')->get('facets.manager')
    );
  }

  /**
   * Autocomplete the label of an entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that contains the typed tags.
   * @param string $facetsource_id
   *   The ID of the facet source.
   * @param string $facet_id
   *   The ID of the facet.
   * @param string $selection_settings_key
   *   The hashed key of the key/value entry that holds the selection handler
   *   settings.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched entity labels as a JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown if the selection settings key is not found in the key/value store
   *   or if it does not match the stored data.
   */
  public function handleAutocomplete(Request $request, $facetsource_id, $facet_id, $selection_settings_key) {
    $matches['results'] = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      // Selection settings are passed in as a hashed key of a serialized array
      // stored in the key/value store.
      $selection_settings = $this->keyValue->get($selection_settings_key, FALSE);
      if ($selection_settings !== FALSE) {
        $selection_settings_hash = Crypt::hmacBase64(serialize($selection_settings) . $facetsource_id . $facet_id, Settings::getHashSalt());
        if ($selection_settings_hash !== $selection_settings_key) {
          // Disallow access when the selection settings hash does not match the
          // passed-in key.
          throw new AccessDeniedHttpException('Invalid selection settings key.');
        }
      }
      else {
        // Disallow access when the selection settings key is not found in the
        // key/value store.
        throw new AccessDeniedHttpException();
      }

      $facets = $this->facetManager->getFacetsByFacetSourceId($facetsource_id);

      $route_parameters['f'] = [];
      foreach ($facets as $facet) {
        $facet->setActiveItems($selection_settings[$facet->id()]);
        if ($facet_id == $facet->id() && $facet->getShowOnlyOneResult()) {
          continue;
        }
        foreach ($selection_settings[$facet->id()] as $setting) {
          $route_parameters['f'][] = $facet->id() . ":$setting";
        }
      }

      $this->facetManager->updateResults($facetsource_id);
      foreach ($facets as $facet) {
        if ($facet->id() == $facet_id) {
          foreach ($facet->getProcessorsByStage(ProcessorInterface::STAGE_BUILD) as $processor) {
            $results = $processor->build($facet, $facet->getResults());
          }

          foreach ($results as $result) {
            $url = $result->getUrl();

            $options = array_filter($url->getOptions()['query'], function ($key) {
              return $key == 'f';
            }, ARRAY_FILTER_USE_KEY);

            $options = NestedArray::mergeDeepArray([$route_parameters, $options]);

            $result->setUrl(new Url(Url::fromUserInput($facet->getFacetSource()->getPath())->getRouteName(), [], ['query' => $options]));

            $matches['results'][] = [
              'id' => $result->getUrl()->toString(),
              'text' => $result->getDisplayValue(),
            ];
          }
        }
      }
    }

    return new JsonResponse($matches);
  }

}
