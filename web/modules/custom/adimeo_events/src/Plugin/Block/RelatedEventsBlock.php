<?php

declare(strict_types = 1);

namespace Drupal\adimeo_events\Plugin\Block;

use Drupal\adimeo_events\Service\EventService;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a list of events related to the current event displayed.
 *
 * @Block(
 *  id = "related_events",
 *  admin_label = @Translation("Related events (Event)"),
 *  category = @Translation("Custom"),
 * )
 */
class RelatedEventsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use MessengerTrait;

  /**
   * The view mode used to render the related events.
   */
  protected const RENDER_VIEW_MODE = 'teaser';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $requestService;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The Event service.
   *
   * @var \Drupal\adimeo_events\Service\EventService
   */
  protected EventService $eventService;

  /**
   * Constructs of RelatedEventsBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match service.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    RequestStack $request,
    LanguageManagerInterface $languageManager,
    CurrentRouteMatch $currentRouteMatch,
    EventService $eventService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->requestService = $request->getCurrentRequest();
    $this->languageManager = $languageManager;
    $this->routeMatch = $currentRouteMatch;
    $this->eventService = $eventService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('language_manager'),
      $container->get('current_route_match'),
      $container->get('adimeo_events.event_service')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function build(): array {
    $eventsList = [];
    $cache = new CacheableMetadata();

    /** @var NodeInterface $currentNode */
    $currentNode = $this->requestService->attributes->get('node');
    if (!$currentNode->hasField('field_event_type')) {
      return $eventsList;
    }

    // Possibility to use the magic method : $currentNode->get('field_event_type')->target_id;
    $eventTypeReferenced = $currentNode->get('field_event_type')->referencedEntities();
    if (empty($eventTypeReferenced)) {
      return $eventsList;
    }

    /** @var Term $eventType */
    $eventType = reset($eventTypeReferenced);
    $eventTypeId = (int) $eventType->id();
    $nodeId = (int) $currentNode->id();

    $relatedEvents = $this->eventService->getEventsByType($eventTypeId, $nodeId);

    if (count($relatedEvents) < EventService::DEFAULT_EVENTS_NUMBERS) {
      $missingEventsNumbers = EventService::DEFAULT_EVENTS_NUMBERS - count($relatedEvents);
      $otherEvents = $this->eventService->getEventsByType($eventTypeId, $nodeId, $missingEventsNumbers, TRUE);
      $relatedEvents = array_merge($relatedEvents, $otherEvents);
    }

    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
    foreach ($relatedEvents as $eventId) {
      /** @var NodeInterface $node */
      $node = $nodeStorage->load($eventId);
      $cache->addCacheableDependency($node);
      $eventsList[] = $viewBuilder->view($node, self::RENDER_VIEW_MODE);
    }

    $content = [
      '#theme' => 'related_events_block',
      '#title' => $this->t('Others events'),
      '#events' => $eventsList,
    ];
    $cache->applyTo($content);
    return $content;
  }

}
