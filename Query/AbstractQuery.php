<?php
namespace FS\SolrBundle\Query;

use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use FS\SolrBundle\Solr;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Update\Query\Document\Document;

abstract class AbstractQuery extends Query
{
    /**
     * @var MetaInformation
     */
    protected $entityMeta;


    /**
     * @var Document
     */
    protected $document = null;

    /**
     *
     * @var Solr
     */
    protected $solr = null;

    /**
     * @param MetaInformation $entityMeta
     */
    public function setEntityMeta(MetaInformation $entityMeta)
    {
        $this->entityMeta = $entityMeta;
    }

    /**
     * @return MetaInformation
     */
    public function getEntityMeta()
    {
        return $this->entityMeta;
    }

    /**
     * @param \Solarium\QueryType\Update\Query\Document\Document $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return \Solarium\QueryType\Update\Query\Document\Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param \FS\SolrBundle\Solr $solr
     */
    public function setSolr($solr)
    {
        $this->solr = $solr;
    }

    /**
     * @return \FS\SolrBundle\Solr
     */
    public function getSolr()
    {
        return $this->solr;
    }

    /**
     * modes defined in FS\SolrBundle\Doctrine\Hydration\HydrationModes
     *
     * @param string $mode
     */
    public function setHydrationMode($mode)
    {
        $this->getSolr()->getMapper()->setHydrationMode($mode);
    }

}
