<?php

declare(strict_types = 1);

namespace Drupal\adimeo_events\HookHandler;

use Drupal\adimeo_events\Plugin\QueueWorker\UnpublishEventsQueueWorker;
use Drupal\adimeo_events\Service\EventServiceInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook handler for the adimeo_events cron hook.
 */
class CronEventHookHandler implements ContainerInjectionInterface {

  /**
   * The last import timestamp state ID.
   */
  public const LAST_RUN_STATE_ID = 'adimeo_events.cron_last_run';

  /**
   * The cron interval (10 min).
   */
  public const RUN_INTERVAL = 600;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * The event service.
   *
   * @var \Drupal\adimeo_events\Service\EventServiceInterface
   */
  protected EventServiceInterface $eventService;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * CronEventHookHandler constructor.
   *
   * @param StateInterface $state
   *   The state service.
   * @param TimeInterface $time
   *   The time service.
   * @param QueueFactory $queue_factory
   *   The queue service.
   * @param LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    StateInterface $state,
    TimeInterface $time,
    EventServiceInterface $eventService,
    QueueFactory $queue_factory,
    LoggerInterface $logger
  ) {
    $this->state = $state;
    $this->time = $time;
    $this->eventService = $eventService;
    $this->queueFactory = $queue_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('state'),
      $container->get('datetime.time'),
      $container->get('adimeo_events.event_service'),
      $container->get('queue'),
      $container->get('logger.channel.adimeo_events')
    );
  }

  /**
   * Enqueue events for run cron if the new execution interval is reached.
   */
  public function process(): void {
    $now = $this->time->getCurrentTime();
    $lastRun = $this->state->get(self::LAST_RUN_STATE_ID, 0);

    // Runs cron each 10 minutes.
    if ($now < $lastRun + self::RUN_INTERVAL) {
      return;
    }

    $this->logger->info('CronEventHookHandler - Start unpublishing events.');
    $queue = $this->queueFactory->get(UnpublishEventsQueueWorker::QUEUE_ID);
    $dateNow = DrupalDateTime::createFromTimestamp($now);
    try {
      $eventsToUnpublish = $this->eventService->loadPastEvents($dateNow);

      /** @var NodeInterface $event */
      foreach($eventsToUnpublish as $eventId) {
        $queue->createItem($eventId);
      }

      $this->state->set(self::LAST_RUN_STATE_ID, $now);
      $this->logger->info('CronEventHookHandler - End of unpublishing events.');
    }
    catch (\Exception $e) {
      $this->logger->error('An error occurred while trying to load past events (CronEventHookHandler). Error code %code - Message : %message', ['%code' => $e->getCode(), '%message' => $e->getMessage()]);
    }
  }

}
