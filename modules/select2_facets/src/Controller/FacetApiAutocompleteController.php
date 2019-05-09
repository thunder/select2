<?php

namespace Drupal\select2_facets\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Site\Settings;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a route controller for facets autocomplete form elements.
 */
class FacetApiAutocompleteController extends ControllerBase {

  /**
   * The facet manager service.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The current router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $router;

  /**
   * The processor manager.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Array of request.
   *
   * @var array
   */
  protected $storedRequests = [];

  /**
   * Constructs a FacetApiAutocompleteController object.
   *
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facetManager
   *   The facet manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   The current path stack.
   * @param \Drupal\Core\Routing\AccessAwareRouterInterface $router
   *   The current router.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $pathProcessor
   *   The processor manager.
   */
  public function __construct(DefaultFacetManager $facetManager, RequestStack $requestStack, CurrentPathStack $currentPathStack, AccessAwareRouterInterface $router, InboundPathProcessorInterface $pathProcessor) {
    $this->facetManager = $facetManager;
    $this->requestStack = $requestStack;
    $this->currentPathStack = $currentPathStack;
    $this->router = $router;
    $this->pathProcessor = $pathProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('facets.manager'),
      $container->get('request_stack'),
      $container->get('path.current'),
      $container->get('router'),
      $container->get('path_processor_manager')
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
   *
   * @throws \Drupal\facets\Exception\InvalidProcessorException
   */
  public function handleAutocomplete(Request $request, $facetsource_id, $facet_id, $selection_settings_key) {
    $matches['results'] = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = mb_strtolower($input);

      // Selection settings are passed in as a hashed key of a serialized array
      // stored in the key/value store.
      $selection_settings = $this->keyValue('entity_autocomplete')->get($selection_settings_key, FALSE);
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
      $new_request = $this->createRequestFromPath($selection_settings['path']);
      $request->attributes->add($this->router->matchRequest($new_request));
      $this->setRequestStack($new_request);

      $facets = $this->facetManager->getFacetsByFacetSourceId($facetsource_id);
      foreach ($facets as $facet) {
        if ($facet->id() != $facet_id) {
          continue;
        }
        $this->facetManager->build($facet);
        foreach ($facet->getResults() as $result) {
          $display_value = mb_strtolower($result->getDisplayValue());
          if ($selection_settings['match_operator'] == 'CONTAINS' && strpos($display_value, $typed_string) === FALSE || ($selection_settings['match_operator'] == 'STARTS_WITH' && strpos($display_value, $typed_string) !== 0)) {
            continue;
          }
          $matches['results'][] = [
            'id' => $result->getUrl()->toString(),
            'text' => ($selection_settings['show_numbers'] ? sprintf('%s (%d)', $result->getDisplayValue(), $result->getCount()) : $result->getDisplayValue()),
          ];
        }
      }
      $this->restoreRequestStack();
    }
    return new JsonResponse($matches);
  }

  /**
   * Creates a new request object from a path.
   *
   * @param string $path
   *   A path with facet arguments.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A new request object.
   */
  protected function createRequestFromPath($path) {
    $new_request = Request::create($path);
    $processed = $this->pathProcessor->processInbound($path, $new_request);
    $this->currentPathStack->setPath($processed);

    return $new_request;
  }

  /**
   * Resets the request stack and adds one request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The one and only request.
   */
  protected function setRequestStack(Request $request) {
    while ($this->requestStack->getCurrentRequest()) {
      $this->storedRequests[] = $this->requestStack->pop();
    }
    $this->requestStack->push($request);
  }

  /**
   * Restore all saved requests on the stack.
   */
  protected function restoreRequestStack() {
    $this->requestStack->pop();
    foreach ($this->storedRequests as $request) {
      $this->requestStack->push($request);
    }
  }

}
