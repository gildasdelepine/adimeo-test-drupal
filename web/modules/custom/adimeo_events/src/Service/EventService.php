<?php

declare(strict_types = 1);

namespace Drupal\adimeo_events\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides helpers for Event content type.
 */
class EventService implements EventServiceInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * EventService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManagerInterface
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManagerInterface,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entityTypeManagerInterface;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventsByType(
    int $eventTypeId,
    int $currentEventId,
    int $rangeLength = self::DEFAULT_EVENTS_NUMBERS,
    bool $excludeType = FALSE
  ): array {
    $events = [];
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $now = new DrupalDateTime();
      $now->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
      $now = $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      $eventTypeCondition = 'IN';
      if ($excludeType === TRUE) {
        $eventTypeCondition = 'NOT IN';
      }

      $events = $nodeStorage->getQuery()
        ->accessCheck()
        ->condition('type', self::NODE_TYPE_EVENT)
        ->condition('status', 1)
        ->condition('nid', $currentEventId, '<>')
        ->condition('field_event_type', $eventTypeId, $eventTypeCondition)
        ->condition('field_date_range.end_value', $now, '>')
        ->sort('field_date_range.value')
        ->range(0, $rangeLength)
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->error('An error occurred while trying to get node storage (getEventsByType). Error code %code - Message : %message', ['%code' => $e->getCode(), '%message' => $e->getMessage()]);
    }

    return $events ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadPastEvents(DrupalDateTime $dateTime = new DrupalDateTime()): array {
    $events = [];
    $dateTime = $dateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $events = $nodeStorage->getQuery()
        ->accessCheck()
        ->condition('type', self::NODE_TYPE_EVENT)
        ->condition('status', 1)
        ->condition('field_date_range.end_value', $dateTime, '<=')
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->error('An error occurred while trying to get node storage (loadPastEvents). Error code %code - Message : %message', ['%code' => $e->getCode(), '%message' => $e->getMessage()]);
    }
    return $events;
  }
}
