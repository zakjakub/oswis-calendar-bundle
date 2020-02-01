<?php
/**
 * @noinspection RedundantDocCommentTagInspection
 * @noinspection PhpUnused
 */

namespace Zakjakub\OswisCalendarBundle\Controller;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zakjakub\OswisCalendarBundle\Entity\Event\Event;
use Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantType;
use Zakjakub\OswisCalendarBundle\Repository\EventParticipantTypeRepository;
use Zakjakub\OswisCalendarBundle\Repository\EventRepository;
use Zakjakub\OswisCalendarBundle\Service\EventParticipantTypeService;
use Zakjakub\OswisCalendarBundle\Service\EventService;
use Zakjakub\OswisCoreBundle\Exceptions\OswisNotFoundException;
use Zakjakub\OswisCoreBundle\Utils\DateTimeUtils;

class EventWebController extends AbstractController
{
    public const RANGE_ALL = null;
    public const RANGE_YEAR = 'year';
    public const RANGE_MONTH = 'month';
    public const RANGE_WEEK = 'week';
    public const RANGE_DAY = 'day';

    protected EventService $eventService;

    protected EventRepository $eventRepository;

    protected EventParticipantTypeService $participantTypeService;

    public function __construct(EventService $eventService, EventParticipantTypeService $participantTypeService)
    {
        $this->eventService = $eventService;
        $this->eventRepository = $eventService->getRepository();
        $this->participantTypeService = $participantTypeService;
    }

    /**
     * @param string|null $slug
     *
     * @return Response
     * @throws LogicException
     * @throws NotFoundHttpException
     */
    final public function showEvent(?string $slug = null): Response
    {
        if (null !== $slug) {
            $this->redirectToRoute('zakjakub_oswis_calendar_web_events');
        }
        $eventRepo = $this->eventService->getRepository();
        $opts = [
            EventRepository::CRITERIA_SLUG                     => $slug,
            EventRepository::CRITERIA_ONLY_PUBLIC_ON_WEB_ROUTE => true,
            EventRepository::CRITERIA_INCLUDE_DELETED          => false,
        ];
        $event = $eventRepo->getEvents($opts, 1);
        if (!($event instanceof Event)) {
            throw new OswisNotFoundException('Událost nenalezena.');
        }
        $navEvents = new ArrayCollection();
        if (null !== $event->getSeries() && null !== $event->getType()) {
            $navEvents = $event->getSeries()->getEvents(
                $event->getType()->getType(),
                $event->isBatch() ? $event->getStartYear() : null
            );
        }
        $participantType = $this->participantTypeService->getRepository()->getEventParticipantTypes(
            [
                EventParticipantTypeRepository::CRITERIA_TYPE_OF_TYPE       => EventParticipantType::TYPE_ATTENDEE,
                EventParticipantTypeRepository::CRITERIA_ONLY_PUBLIC_ON_WEB => true,
            ]
        )->first();
        $data = array(
            'navEvents'       => $navEvents,
            'event'           => $event,
            'participantType' => $participantType,
        );

        return $this->render('@ZakjakubOswisCalendar/web/pages/event.html.twig', $data);
    }

    /**
     * @param DateTimeUtils $dateTimeUtils
     * @param string|null   $range
     * @param DateTime|null $start
     * @param DateTime|null $end
     * @param int|null      $limit
     * @param int|null      $offset
     *
     * @return Response
     * @throws LogicException
     * @throws Exception
     */
    final public function showEvents(
        DateTimeUtils $dateTimeUtils,
        ?string $range = null,
        ?DateTime $start = null,
        ?DateTime $end = null,
        ?int $limit = null,
        ?int $offset = null
    ): Response {
        $range ??= self::RANGE_ALL;
        $limit = $limit < 1 ? null : $limit;
        $offset = $offset < 1 ? null : $offset;
        $start = $dateTimeUtils->getDateTimeByRange($start, $range, false);
        $end = $dateTimeUtils->getDateTimeByRange($end, $range, true);
        $opts = [
            EventRepository::CRITERIA_START              => $start,
            EventRepository::CRITERIA_END                => $end,
            EventRepository::CRITERIA_INCLUDE_DELETED    => false,
            EventRepository::CRITERIA_ONLY_PUBLIC_ON_WEB => true,
        ];
        $events = $this->eventRepository->getEvents($opts, $limit, $offset);
        $opts = [
            EventRepository::CRITERIA_START              => $start,
            EventRepository::CRITERIA_END                => $end,
            EventRepository::CRITERIA_INCLUDE_DELETED    => false,
            EventRepository::CRITERIA_ONLY_PUBLIC_ON_WEB => true,
            EventRepository::CRITERIA_ONLY_WITHOUT_DATE  => true,
        ];
        $withoutDateEvents = $this->eventRepository->getEvents($opts);
        $context = [
            'events'            => $events,
            'navEvents'         => [],
            'withoutDateEvents' => $withoutDateEvents,
        ];

        return $this->render('@ZakjakubOswisCalendar/web/pages/events.html.twig', $context);
    }
}
