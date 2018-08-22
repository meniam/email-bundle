<?php

namespace Super\SuperEmailBundle\Message;

use Super\SuperEmailBundle\Event\SendEvent;
use Super\SuperEmailBundle\SuperEmailEvent;
use Super\SuperEmailBundle\Swift\SmtpTransport;
use Swift_Mailer;
use Swift_TransportException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Sender
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var Swift_Mailer */
    protected $mailer;

    /** @var Swift_Mailer */
    protected $fallbackMailer;

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setPrimaryMailer(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function setFallbackMailer(Swift_Mailer $mailer)
    {
        $this->fallbackMailer = $mailer;
    }

    /**
     * @param Message $message
     * @return Sender
     * @throws Swift_TransportException
     * @throws \Swift_DependencyException
     */
    public function send(Message $message)
    {
        $this->eventDispatcher->dispatch(SuperEmailEvent::BEFORE_SEND, new SendEvent($message));
        try {
            $this->internalSend($this->mailer, $message);
        } catch (Swift_TransportException $exception) {
            if ($this->fallbackMailer) {
                $this->internalSend($this->fallbackMailer, $message);
            } else {
                throw $exception;
            }
        }

        return $this;
    }

    /**
     * @param Swift_Mailer $mailer
     * @param Message      $message
     * @throws \Swift_DependencyException
     * @throws Swift_TransportException
     */
    protected function internalSend(Swift_Mailer $mailer, Message $message)
    {
        /** @var SmtpTransport $transport */
        $transport = $mailer->getTransport();
        try {
            $mailer->send($message->getSwiftMessage());
            if ($transport instanceof SmtpTransport) {
                $server = $transport->getHost();
                $eximId = $transport->getLastEximId();
            } else {
                $server = null;
                $eximId = null;
            }
            $this->eventDispatcher->dispatch(SuperEmailEvent::AFTER_SEND, new SendEvent($message, $server, $eximId));
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (Swift_TransportException $exception) {
            $transport->stop();
            throw $exception;
        }
    }
}