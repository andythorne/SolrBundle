<?php
namespace FS\SolrBundle\Doctrine\Mapper;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use FS\SolrBundle\Doctrine\Hydration\Exception\HydratorNotFoundException;
use FS\SolrBundle\Doctrine\Hydration\HydrationModes;
use FS\SolrBundle\Doctrine\Hydration\Hydrator;
use FS\SolrBundle\Doctrine\Hydration\HydratorInterface;
use FS\SolrBundle\Doctrine\Mapper\Mapping\AbstractDocumentCommand;
use FS\SolrBundle\Doctrine\Annotation\Index as Solr;
use FS\SolrBundle\Query\AbstractQuery;
use Solarium\QueryType\Select\Result\Result;
use Solarium\QueryType\Update\Query\Document\Document;

class EntityMapper
{
    /**
     * @var CreateDocumentCommandInterface
     */
    private $mappingCommand = null;

    /**
     * @var string
     */
    private $hydrationMode = '';

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var HydratorInterface[]
     */
    private $hydrators = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->hydrationMode = HydrationModes::HYDRATE_DOCTRINE;
    }

    /**
     * Add a hydrator to the stack
     *
     * @param HydratorInterface $hydrator
     */
    public function addHydrator(HydratorInterface $hydrator)
    {
        $this->hydrators[] = $hydrator;
    }

    /**
     * @param AbstractDocumentCommand $command
     */
    public function setMappingCommand(AbstractDocumentCommand $command)
    {
        $this->mappingCommand = $command;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param                 $entity
     * @param MetaInformation $meta
     *
     * @return Document
     */
    public function toDocument($entity, MetaInformation $meta)
    {
        if ($this->mappingCommand instanceof AbstractDocumentCommand) {
            return $this->mappingCommand->createDocument($entity, $meta);
        }

        return null;
    }

    /**
     * @param array           $documents
     * @param MetaInformation $meta
     * @param int             $hydration
     *
     * @throws HydratorNotFoundException
     * @return mixed
     */
    public function fromResponse(array $documents, MetaInformation $meta, $hydration = Query::HYDRATE_OBJECT)
    {
        foreach($this->hydrators as $hydrator)
        {
            if($hydrator->supports($this->hydrationMode))
            {
                return $hydrator->hydrate($documents, $meta, $this->queryBuilder, $hydration);
            }
        }

        throw new HydratorNotFoundException();
    }

    /**
     * @param string $mode
     */
    public function setHydrationMode($mode)
    {
        $this->hydrationMode = $mode;
    }

}
