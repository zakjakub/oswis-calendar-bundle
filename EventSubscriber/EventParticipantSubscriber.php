<?php

namespace Zakjakub\OswisCalendarBundle\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Zakjakub\OswisCalendarBundle\Entity\EventParticipant\EventParticipant;
use Zakjakub\OswisCalendarBundle\Exceptions\OswisEventParticipantNotFoundException;
use Zakjakub\OswisCalendarBundle\Manager\EventParticipantManager;
use Zakjakub\OswisCoreBundle\Provider\OswisCoreSettingsProvider;
use function assert;
use function in_array;

/**
 * Class EventParticipantSubscriber
 * @package Zakjakub\OswisCalendarBundle\EventSubscriber
 */
final class EventParticipantSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $em;

    private MailerInterface $mailer;

    private LoggerInterface $logger;

    private OswisCoreSettingsProvider $oswisCoreSettings;

    private UserPasswordEncoderInterface $encoder;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger, OswisCoreSettingsProvider $oswisCoreSettings, UserPasswordEncoderInterface $encoder)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->oswisCoreSettings = $oswisCoreSettings;
        $this->encoder = $encoder;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['postWrite', EventPriorities::POST_WRITE],
                ['postValidate', EventPriorities::POST_VALIDATE],
            ],
        ];
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function postWrite(ViewEvent $event): void
    {
        $eventParticipant = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        if (!$eventParticipant instanceof EventParticipant || !in_array($method, [Request::METHOD_POST, Request::METHOD_PUT], true)) {
            return;
        }
        $eventParticipantRepository = $this->em->getRepository(EventParticipant::class);
        $eventParticipant = $eventParticipantRepository->findOneBy(['id' => $eventParticipant->getId()]);
        assert($eventParticipant instanceof EventParticipant);
        if ($eventParticipant) {
            $eventParticipantManager = new EventParticipantManager($this->em, $this->mailer, $this->oswisCoreSettings, $this->logger);
            $eventParticipantManager->sendMail($eventParticipant, $this->encoder, Request::METHOD_POST === $method);
        } else {
            throw new OswisEventParticipantNotFoundException();
        }
    }

    /**
     * @param ViewEvent $event
     *
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function postValidate(ViewEvent $event): void
    {
        $newEventParticipant = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        if (!$newEventParticipant instanceof EventParticipant || $method !== Request::METHOD_PUT) {
            return;
        }
        $eventParticipant = $this->getExistingEventParticipant($newEventParticipant);
        if ($eventParticipant) {
            $newEventParticipant->setEMailConfirmationDateTime(null);
            $eventParticipant->setEMailConfirmationDateTime(null);
        } else {
            throw new OswisEventParticipantNotFoundException();
        }
    }

    private function getExistingEventParticipant(EventParticipant $newEventParticipant): ?EventParticipant
    {
        $eventParticipantRepository = $this->em->getRepository(EventParticipant::class);
        $eventParticipant = $eventParticipantRepository->findOneBy(['id' => $newEventParticipant->getId()]);
        assert($eventParticipant instanceof EventParticipant);

        return $eventParticipant;
    }
}
