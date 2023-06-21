<?php

declare(strict_types = 1);

namespace Drupal\adimeo_events\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker to unpublish past events.
 *
 * @QueueWorker(
 *     id = "adimeo_events_unpublish_events",
 *     title = @Translation("Unpublish past events"),
 *     cron = {"time": 10}
 * )
 */
class UnpublishEventsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * This queue ID.
   */
  public const QUEUE_ID = 'adimeo_events_unpublish_events';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (empty($data)) {
      return;
    }
    $eventEntity = $this->entityTypeManager->getStorage('node')->load($data);
    if ($eventEntity instanceof NodeInterface) {
      $eventEntity->setUnpublished();
      $eventEntity->save();
    }
  }

}
