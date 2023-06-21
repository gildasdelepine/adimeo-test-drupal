<?php

declare(strict_types = 1);

namespace Drupal\adimeo_events\Service;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides helpers for Event content type.
 */
interface EventServiceInterface {

  /**
   * The machine name of the node type event.
   */
  public const NODE_TYPE_EVENT = 'event';

  /**
   * The default number of events to recover.
   */
  public const DEFAULT_EVENTS_NUMBERS = 3;

  /**
   * Get a list of events filtered by their type and date.
   *
   * @param int $eventTypeId
   *   The event type Term id.
   * @param int $currentEventId
   *   The current event id.
   * @param int $rangeLength
   *   The number of events to recover.
   * @param bool $excludeType
   *   Flag indicates if the event type must be excluded from the query.
   *
   * @return array
   *   The list of matching events.
   */
  public function getEventsByType(int $eventTypeId, int $currentEventId, int $rangeLength = self::DEFAULT_EVENTS_NUMBERS, bool $excludeType = FALSE): array;

  /**
   * Returns the events having the end date older than the date past in parameter.
   *
   * @param DrupalDateTime $dateTime
   *    The date used to define the old events (unpublish events older than this date).
   *
   * @return array
   *    The list of events (empty if no result or if an error occurs).
   */
  public function loadPastEvents(DrupalDateTime $dateTime = new DrupalDateTime()): array;
}
