<?php

namespace FS\SolrBundle\Doctrine\Hydration;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use FS\SolrBundle\Doctrine\Annotation\CollectionField;
use FS\SolrBundle\Doctrine\Annotation\EntityField;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DoctrineHydrator extends AbstractMergableHydrator implements HydratorInterface
{

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @inheritdoc
     */
    public function supports($method)
    {
        return $method === HydrationModes::HYDRATE_DOCTRINE;
    }

    /**
     * @inheritdoc
     */
    public function hydrate($documents, MetaInformation $meta, QueryBuilder $qb = null, $hydration = Query::HYDRATE_OBJECT)
    {
        $id       = $meta->getIdentifier();
        $entities = array();

        foreach($documents as $document)
        {
            $entities[$document->id] = $document;
        }

        $ids = array_keys($entities);

        if(!($qb instanceof QueryBuilder))
        {
            /** @var EntityManager $em */
            $em = $this->doctrine->getManager();

            $qb = $em->createQueryBuilder();

            $qb->select('e')
                ->from($meta->getClassName(), 'e');

            // fetch collections and entities as well
            foreach($meta->getFields() as $i=>$field)
            {
                if($field instanceof EntityField || $field instanceof CollectionField)
                {
                    $f = "f{$i}";
                    $qb->leftJoin('e.'.$field->field, $f)->addSelect($f);
                }
            }

        }

        $aliases = $qb->getRootAliases();

        $qb->andWhere($aliases[0].'.'.$id->name.' IN (:ids)')
           ->setParameter('ids', $ids);

        $doctrineEntities = $qb->getQuery()->getResult($hydration);

        return $doctrineEntities;

    }
}
