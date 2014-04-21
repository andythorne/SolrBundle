<?php

namespace FS\SolrBundle\Doctrine\Hydration;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;

interface HydratorInterface
{
    /**
     * Decide if this hydration class supports the hydration method
     *
     * @param int $method One of the hydration constants
     *
     * @return boolean
     */
    public function supports($method);

    /**
     * @param array             $documents
     * @param MetaInformation   $meta
     * @param QueryBuilder|null $qb
     * @param int               $hydration
     *
     * @return array
     */
    public function hydrate($documents, MetaInformation $meta, QueryBuilder $qb = null, $hydration = Query::HYDRATE_OBJECT);
} 
