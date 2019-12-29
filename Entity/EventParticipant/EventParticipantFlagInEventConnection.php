<?php

namespace Zakjakub\OswisCalendarBundle\Entity\EventParticipant;

use Doctrine\ORM\Mapping as ORM;
use Zakjakub\OswisCalendarBundle\Entity\Event\Event;
use Zakjakub\OswisCoreBundle\Traits\Entity\ActiveTrait;
use Zakjakub\OswisCoreBundle\Traits\Entity\BasicEntityTrait;

/**
 * @Doctrine\ORM\Mapping\Entity()
 * @Doctrine\ORM\Mapping\Table(name="calendar_event_participant_flag_in_event_connection")
 * @Doctrine\ORM\Mapping\Cache(usage="NONSTRICT_READ_WRITE", region="calendar_event_participant")
 */
class EventParticipantFlagInEventConnection
{
    use BasicEntityTrait;
    use ActiveTrait;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $maxAmountInEvent = null;

    /**
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantFlag",
     *     inversedBy="eventParticipantFlagInEventConnections",
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?EventParticipantFlag $eventParticipantFlag = null;

    /**
     * Event contact (connected to person or organization).
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\Event\Event",
     *     inversedBy="eventParticipantFlagInEventConnections",
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?Event $event = null;

    /**
     * Event contact type.
     * @Doctrine\ORM\Mapping\ManyToOne(
     *     targetEntity="Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipantType",
     *     fetch="EAGER"
     * )
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?EventParticipantType $eventParticipantType = null;

    /**
     * @param EventParticipantFlag|null $eventParticipantFlag
     * @param Event|null                $event
     * @param EventParticipantType|null $eventParticipantType
     * @param int|null                  $maxAmountInEvent
     */
    public function __construct(
        ?EventParticipantFlag $eventParticipantFlag = null,
        ?Event $event = null,
        ?EventParticipantType $eventParticipantType = null,
        ?int $maxAmountInEvent = null
    ) {
        $this->setEventParticipantFlag($eventParticipantFlag);
        $this->setEventParticipantType($eventParticipantType);
        $this->setEvent($event);
        $this->setMaxAmountInEvent($maxAmountInEvent);
    }

    final public function getEventParticipantType(): ?EventParticipantType
    {
        return $this->eventParticipantType;
    }

    final public function setEventParticipantType(?EventParticipantType $eventParticipantType): void
    {
        $this->eventParticipantType = $eventParticipantType;
    }

    /**
     * @return int|null
     */
    final public function getMaxAmountInEvent(): ?int
    {
        return $this->maxAmountInEvent;
    }

    /**
     * @param int|null $maxAmountInEvent
     */
    final public function setMaxAmountInEvent(?int $maxAmountInEvent): void
    {
        $this->maxAmountInEvent = $maxAmountInEvent;
    }

    final public function getEvent(): ?Event
    {
        return $this->event;
    }

    final public function setEvent(?Event $event): void
    {
        if ($this->event && $event !== $this->event) {
            $this->event->removeEventParticipantFlagInEventConnection($this);
        }
        if ($event && $this->event !== $event) {
            $this->event = $event;
            $event->addEventParticipantFlagInEventConnection($this);
        }
    }

    final public function getEventParticipantFlag(): ?EventParticipantFlag
    {
        return $this->eventParticipantFlag;
    }

    final public function setEventParticipantFlag(?EventParticipantFlag $eventParticipantFlag): void
    {
        $this->eventParticipantFlag = $eventParticipantFlag;
    }
}
