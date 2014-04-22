<?php

namespace FS\SolrBundle\Console;


use FS\SolrBundle\Event\ErrorEvent;
use FS\SolrBundle\Event\Event;

class ConsoleResultFactory
{

    /**
     * @param Event $event
     * @return CommandResult
     */
    public function fromEvent(Event $event)
    {
        return new CommandResult(
            $this->getResultId($event),
            $this->getClassname($event),
            $this->getMessage($event)
        );

    }

    /**
     * @param Event $event
     * @return null|number
     */
    private function getResultId(Event $event)
    {
        if ($event->getEntity() == null) {
            return null;
        }

        return $event->getEntity()->getId();
    }

    /**
     * @param Event $event
     * @return string
     */
    private function getClassname(Event $event)
    {
        if ($event->getMetaInformation() == null) {
            return '';
        }

        return $event->getMetaInformation()->getClassName();
    }

    /**
     * @param Event $event
     * @return string
     */
    private function getMessage(Event $event)
    {
        if (!$event instanceof ErrorEvent) {
            return '';
        }

        return $event->getExceptionMessage();
    }
} 
