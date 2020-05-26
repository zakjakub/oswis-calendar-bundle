<?php
/**
 * @noinspection MethodShouldBeFinalInspection
 * @noinspection PhpUnused
 */

namespace OswisOrg\OswisCalendarBundle\Entity\Event;

use DateTime;
use OswisOrg\OswisCalendarBundle\Entity\AbstractClass\AbstractEventFlagConnection;

/**
 * @Doctrine\ORM\Mapping\Entity()
 * @Doctrine\ORM\Mapping\Table(name="calendar_event_flag_new_connection")
 * @Doctrine\ORM\Mapping\Cache(usage="NONSTRICT_READ_WRITE", region="calendar_event")
 */
class EventFlagConnection extends AbstractEventFlagConnection
{
    /**
     * Event flag.
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\EventFlag", fetch="EAGER")
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?EventFlag $eventFlag = null;

    /**
     * Event.
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="OswisOrg\OswisCalendarBundle\Entity\Event\Event", inversedBy="eventFlagConnections")
     * @Doctrine\ORM\Mapping\JoinColumn(nullable=true)
     */
    protected ?Event $event = null;

    public function __construct(
        ?EventFlag $eventFlag = null,
        ?Event $event = null,
        ?string $textValue = null,
        ?DateTime $startDateTime = null,
        ?DateTime $endDateTime = null
    ) {
        parent::__construct($textValue, $startDateTime, $endDateTime);
        $this->setEventFlag($eventFlag);
        $this->setEvent($event);
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): void
    {
        if ($this->event && $event !== $this->event) {
            $this->event->removeEventFlagConnection($this);
        }
        if ($event && $this->event !== $event) {
            $this->event = $event;
            $event->addEventFlagConnection($this);
        }
    }

    public function getEventFlag(): ?EventFlag
    {
        return $this->eventFlag;
    }

    public function setEventFlag(?EventFlag $eventFlag): void
    {
        $this->eventFlag = $eventFlag;
    }
}
