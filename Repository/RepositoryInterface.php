<?php
namespace FS\SolrBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use FS\SolrBundle\Query\SolrQuery;
use Solarium\QueryType\Update\Query\Document\DocumentInterface;

interface RepositoryInterface
{

    /**
     * @param array $args
     * @param int   $limit
     *
     * @return array
     */
    public function findBy(array $args, $limit = 10);

    /**
     * @param int $id
     *
     * @return object
     */
    public function find($id);

    /**
     * @param array $args
     *
     * @return object
     */
    public function findOneBy(array $args);

    /**
     * @param int $limit
     *
     * @return array
     */
    public function findAll($limit = 10);

    /**
     * Uses queryAllFields to find all entities
     *
     * @param array $criteria
     * @param int   $limit
     *
     * @return mixed
     */
    public function findAllBy(array $criteria, $limit = 10);


    /**
     * @param array             $args      Filter in solr
     * @param array|null        $fields    Fields to fetch from solr. If blank, will fetch all
     * @param int               $limit     Limit the row count
     * @param QueryBuilder|null $qb        Doctrine query builder
     * @param int               $hydration One of the \Doctrine\ORM\Query::HYDRATE_ constants
     *
     * @return mixed
     */
    public function createFindBy(array $args, array $fields = null, $limit = 10, QueryBuilder $qb = null, $hydration = Query::HYDRATE_OBJECT);

    /**
     * Decide if the entity should be indexed
     *
     * @param $entity
     *
     * @return boolean
     */
    public function shouldIndex($entity);

    /**
     * @param object $entity
     *
     * @return boolean
     */
    public function update($entity);

    /**
     * @param object $entity
     *
     * @return mixed
     */
    public function insert($entity);

    /**
     * @param object $entity
     *
     * @return mixed
     */
    public function delete($entity);

    /**
     * Convert an entity into a document
     *
     * @param $entity
     *
     * @return DocumentInterface
     */
    public function toDocument($entity);

    /**
     * @param QueryBuilder $qb
     *
     * @return SolrQuery
     */
    //public function createQuery(QueryBuilder $qb);
}
