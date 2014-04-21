<?php

namespace FS\SolrBundle\Repository;


use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use FS\SolrBundle\Doctrine\Hydration\HydrationModes;
use FS\SolrBundle\Doctrine\Mapper\EntityMapper;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use FS\SolrBundle\Query\SolrQuery;
use FS\SolrBundle\Solr;

class Repository implements RepositoryInterface
{
    /**
     * @var MetaInformation
     */
    private $meta;

    /**
     * @var Solr
     */
    private $solr = null;

    /**
     * @var EntityMapper
     */
    private $mapper;

    /**
     * @var string
     */
    protected $hydrationMode = HydrationModes::HYDRATE_DOCTRINE;

    /**
     * @param Solr            $solr
     * @param MetaInformation $meta
     */
    function __construct(Solr $solr, MetaInformation $meta)
    {
        $this->solr = $solr;
        $this->meta = $meta;
        $this->mapper = $this->solr->getMapper();
    }

    /**
     * @return SolrQuery
     */
    private function getSolrQuery()
    {
        $this->mapper->setMappingCommand($this->solr->getCommandFactory()->get('all'));

        $query = new SolrQuery();
        $query->setEntityMeta($this->meta);
        $query->setSolr($this->solr);
        $query->setHydrationMode($this->hydrationMode);

        return $query;
    }


    /**
     * @param int $id
     *
     * @return object|null
     */
    public function find($id)
    {
        $query = $this->getSolrQuery();

        $searchId = $this->meta->getIdentifier();
        $query->addSearchTerm($searchId->name, $id);
        $query->setRows(1);

        $response = $this->solr->query($query);

        if(!count($response))
        {
            return null;
        }

        $documents = $response->getDocuments();
        $results = $this->mapper->fromResponse(array(array_pop($documents)), $this->meta);
        return array_pop($results);
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    public function findAll($limit=10)
    {
        $query = $this->getSolrQuery();
        $query->setRows($limit);

        $searchId = $this->meta->getIdentifier();
        $query->addSearchTerm($searchId->name, '*');

        $response = $this->solr->query($query);
        if(!count($response))
        {
            return array();
        }

        return $this->mapper->fromResponse($response->getDocuments(), $this->meta);
    }


    /**
     * @param array $criteria
     * @param int   $limit
     *
     * @return array
     */
    public function findAllBy(array $criteria, $limit=10)
    {
        $query = $this->getSolrQuery();
        $query->queryAllFields($criteria);

        $response = $this->solr->query($query);
        if(!count($response))
        {
            return array();
        }

        return $this->mapper->fromResponse($response->getDocuments(), $this->meta);
    }

    /**
     * @param array $args
     * @param int   $limit
     *
     * @return array
     */
    public function findBy(array $args, $limit=10)
    {
        $query = $this->getSolrQuery();
        $query->setRows($limit);

        foreach($args as $fieldName => $fieldValue)
        {
            $query->addSearchTerm($fieldName, $fieldValue);
        }

        $response = $this->solr->query($query);

        if(!count($response))
            return array();

        return $this->mapper->fromResponse($response->getDocuments(), $this->meta);
    }

    /**
     * @param array $args
     *
     * @return object
     */
    public function findOneBy(array $args)
    {
        $query = $this->getSolrQuery();

        foreach($args as $fieldName => $fieldValue)
        {
            $query->addSearchTerm($fieldName, $fieldValue);
        }

        $query->setRows(1);
        $response = $this->solr->query($query);

        if(count($response) == 0)
        {
            return null;
        }

        $documents = $response->getDocuments();
        $results = $this->mapper->fromResponse(array(array_pop($documents)), $this->meta);
        return array_pop($results);
    }

    /**
     * @param array        $args
     * @param array        $fields
     * @param int          $limit
     * @param QueryBuilder $qb
     * @param int          $hydration
     *
     * @return array
     */
    public function createFindBy(array $args, array $fields = null, $limit=10, QueryBuilder $qb = null, $hydration = Query::HYDRATE_OBJECT)
    {
        $query = $this->getSolrQuery();
        $query->setRows($limit);

        foreach($args as $fieldName => $fieldValue)
        {
            $query->addSearchTerm($fieldName, $fieldValue);
        }

        if($fields)
        {
            foreach($fields as $field)
            {
                $query->addField($field);
            }

        }

        if($qb)
        {
            $this->mapper->setQueryBuilder($qb);
        }

        $response = $this->solr->query($query);

        if(!count($response))
        {
            return array();
        }

        return $this->mapper->fromResponse($response->getDocuments(), $this->meta, $hydration);
    }



    /**
     *  META METHODS
     */


    /**
     * @param object $entity
     *
     * @throws \BadMethodCallException if callback method not exists
     * @return boolean
     */
    public function shouldIndex($entity)
    {
        if(!$this->meta->hasSynchronizationFilter())
        {
            return true;
        }

        $callback = $this->meta->getSynchronizationCallback();
        if(!method_exists($entity, $callback))
        {
            throw new \BadMethodCallException(sprintf('unknown method %s in entity %s', $callback, get_class($entity)));
        }

        return $entity->$callback();
    }

    /**
     * @inheritdoc
     */
    public function insert($entity)
    {
        if(!$this->shouldIndex($entity))
        {
            return;
        }

        $document = $this->toDocument($entity);

        $this->solr->addDocument($document, $this->meta);
    }

    /**
     * @inheritdoc
     */
    public function update($entity)
    {
        $document = $this->toDocument($entity);

        $this->solr->updateDocument($document, $this->meta);
    }

    /**
     * @inheritdoc
     */
    public function delete($entity)
    {
        $document = $this->toDocument($entity);

        $this->solr->removeDocument($document, $this->meta);
    }

    /**
     * @inheritdoc
     */
    public function toDocument($entity)
    {

        $this->mapper->setMappingCommand($this->solr->getCommandFactory()->get('all'));
        $doc = $this->mapper->toDocument($entity, $this->meta);

        return $doc;
    }
} 
