<?php
namespace FS\SolrBundle\Event;

use Symfony\Component\EventDispatcher\Event as BaseEvent;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;

class Event extends BaseEvent
{

    /**
     * @var object
     */
    private $client = null;

    /**
     * @var MetaInformation
     */
    private $metainformation = null;

    /**
     * something like 'update-solr-document'
     *
     * @var string
     */
    private $solrAction = '';

    /**
     * @var Event
     */
    private $sourceEvent;

    /**
     * @var object
     */
    private $entity;

    /**
     * @param object          $client
     * @param MetaInformation $metainformation
     * @param object          $entity
     * @param string          $solrAction
     * @param Event           $sourceEvent
     */
    public function __construct(
        $client = null,
        $entity = null,
        MetaInformation $metainformation = null,
        $solrAction = '',
        Event $sourceEvent = null
    )
    {
        $this->client = $client;
        $this->metainformation = $metainformation;
        $this->entity = $entity;
        $this->solrAction = $solrAction;
        $this->sourceEvent = $sourceEvent;
    }

    /**
     * @return MetaInformation
     */
    public function getMetaInformation()
    {
        return $this->metainformation;
    }

    /**
     * @return string
     */
    public function getSolrAction()
    {
        return $this->solrAction;
    }

    /**
     * @return Event
     */
    public function getSourceEvent()
    {
        return $this->sourceEvent;
    }

    /**
     * @return bool
     */
    public function hasSourceEvent()
    {
        return $this->sourceEvent !== null;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }


}
