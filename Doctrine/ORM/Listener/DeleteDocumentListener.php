<?php
namespace FS\SolrBundle\Doctrine\ORM\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use FS\SolrBundle\Repository\Exception\RepositoryNotFoundException;
use FS\SolrBundle\Solr;

class DeleteDocumentListener
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
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        try {
            $repo = $this->solr->getRepository($entity);
            $repo->delete($entity);
        } catch(RepositoryNotFoundException $e){
        } catch (\RuntimeException $e) {
        }
    }
}
