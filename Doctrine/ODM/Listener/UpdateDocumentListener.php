<?php
namespace FS\SolrBundle\Doctrine\ODM\Listener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use FS\SolrBundle\Solr;
use FS\SolrBundle\SolrQueryFacade;

class UpdateDocumentListener
{

    /**
     * @var Solr
     */
    private $solr = null;

    /**
     * @param Solr $solr
     */
    public function __construct(Solr $solr)
    {
        $this->solr = $solr;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getDocument();

        try {
            $repo = $this->solr->getRepository($entity);
            $repo->update($entity);
        } catch (\RuntimeException $e) {
        }
    }
}
