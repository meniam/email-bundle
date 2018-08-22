<?php

namespace Super\SuperEmailBundle;

use Super\SuperEmailBundle\Message\Message;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

trait SuperEmailAwareTrait
{
    use ContainerAwareTrait;

    protected function getDefaultMessage() { return 'default'; }

    /**
     * @return Message|object
     */
    protected function createMessage()
    {
        return $this->container->get('super_email.' . $this->getDefaultMessage() . '.message');
    }

    /**
     * @param UserInterface $user
     * @param null $template
     * @param array $vars
     * @return Message
     */
    protected function createMessageForUser(UserInterface $user, $template = null, $vars = [ ])
    {
        return $this->createMessage()->createForUser($user, $template, $vars);
    }
}
