<?php

namespace FS\SolrBundle\Doctrine\Hydration;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;

/**
 * hydrates Entity from Document
 */
class IndexHydrator extends AbstractMergableHydrator implements HydratorInterface
{

    /**
     * @inheritdoc
     */
    public function supports($method)
    {
        return $method === HydrationModes::HYDRATE_INDEX;
    }

    /**
     * @inheritdoc
     */
    public function hydrate($documents, MetaInformation $meta, QueryBuilder $qb = null, $hydration = Query::HYDRATE_OBJECT)
    {
        $entities = array();

        $sourceTargetEntity = $meta->getClassName();

        foreach($documents as $document)
        {
            $entities[] = $this->merge(new $sourceTargetEntity(), $document) ;
        }

        return $entities;
    }
} 
