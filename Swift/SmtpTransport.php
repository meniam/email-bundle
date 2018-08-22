<?php
namespace Super\SuperEmailBundle\Swift;

use Swift_Events_EventDispatcher;
use Swift_Mime_SimpleMessage;
use Swift_Transport_EsmtpTransport;
use Swift_Transport_IoBuffer;

class SmtpTransport extends Swift_Transport_EsmtpTransport
{
    protected $hosts;
    protected $lastEximId;

    /**
     * SmtpTransport constructor.
     *
     * @param Swift_Transport_IoBuffer     $buf
     * @param                              $extensionHandlers
     * @param Swift_Events_EventDispatcher $dispatcher
     */
    public function __construct(Swift_Transport_IoBuffer $buf, $extensionHandlers, Swift_Events_EventDispatcher $dispatcher)
    {
        parent::__construct($buf, $extensionHandlers, $dispatcher);
        $this->registerPlugin(new EximIdPlugin());
    }

    /**
     * @param string $host
     * @return Swift_Transport_EsmtpTransport
     */
    public function setHost($host)
    {
        if (is_array($host)) {
            $this->setHosts($host);
        }
        return parent::setHost($host);
    }

    /**
     * @param $hosts
     */
    protected function setHosts($hosts)
    {
        $this->hosts = $hosts;
        $this->selectHost();
    }

    protected function selectHost()
    {
        if ($this->hosts) {
            $this->setHost($this->hosts[array_rand($this->hosts)]);
        }
    }

    public function stop()
    {
        parent::stop();
        $this->selectHost();
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param null                     $failedRecipients
     * @return int
     * @throws \Exception
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->lastEximId = null;
        $result = parent::send($message, $failedRecipients);
        return $result;
    }

    /**
     * @param $id
     */
    public function setLastEximId($id)
    {
        $this->lastEximId = $id;
    }

    /**
     * @return mixed
     */
    public function getLastEximId()
    {
        return $this->lastEximId;
    }
}
