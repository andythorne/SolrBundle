<?php
namespace FS\SolrBundle;

use Doctrine\ORM\Query;
use FS\SolrBundle\Doctrine\Mapper\EntityMapper;
use FS\SolrBundle\Doctrine\Mapper\Mapping\CommandFactory;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use FS\SolrBundle\Doctrine\Mapper\MetaInformationFactory;
use FS\SolrBundle\Event\ErrorEvent;
use FS\SolrBundle\Event\Event;
use FS\SolrBundle\Event\Events;
use FS\SolrBundle\Query\AbstractQuery;
use FS\SolrBundle\Query\SolrQuery;
use FS\SolrBundle\Repository\Exception\RepositoryNotFoundException;
use FS\SolrBundle\Repository\Repository;
use FS\SolrBundle\Repository\RepositoryInterface;
use Solarium\Client;
use Solarium\QueryType\Select\Result\Result;
use Solarium\QueryType\Update\Query\Document\Document;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Solr
{


    /**
     * @var Client
     */
    private $solrClient = null;

    /**
     * @var EntityMapper
     */
    private $entityMapper = null;

    /**
     * @var CommandFactory
     */
    private $commandFactory = null;

    /**
     * @var EventDispatcherInterface
     */
    private $eventManager = null;

    /**
     * @var MetaInformationFactory
     */
    private $metaInformationFactory = null;

    /**
     * @var int numFound
     */
    private $numberOfFoundDocuments = 0;

    /**
     * @param Client                       $client
     * @param CommandFactory               $commandFactory
     * @param EventDispatcherInterface     $manager
     * @param MetaInformationFactory       $metaInformationFactory
     * @param Doctrine\Mapper\EntityMapper $entityMapper
     */
    public function __construct(
        Client $client,
        CommandFactory $commandFactory,
        EventDispatcherInterface $manager,
        MetaInformationFactory $metaInformationFactory,
        EntityMapper $entityMapper
    )
    {
        $this->solrClient             = $client;
        $this->commandFactory         = $commandFactory;
        $this->eventManager           = $manager;
        $this->metaInformationFactory = $metaInformationFactory;

        $this->entityMapper = $entityMapper;
    }

    /**
     * @return EntityMapper
     */
    public function getMapper()
    {
        return $this->entityMapper;
    }

    /**
     * @return CommandFactory
     */
    public function getCommandFactory()
    {
        return $this->commandFactory;
    }

    /**
     * @return MetaInformationFactory
     */
    public function getMetaFactory()
    {
        return $this->metaInformationFactory;
    }

    /**
     * @param string $entityAlias
     *
     * @throws \RuntimeException
     * @return RepositoryInterface
     */
    public function getRepository($entityAlias)
    {
        try
        {
            $metaInformation = $this->metaInformationFactory->loadInformation($entityAlias);
        }
        catch(\RuntimeException $e)
        {
            throw new RepositoryNotFoundException(get_class($entityAlias), $e);
        }

        $repositoryClass = $metaInformation->getRepository();
        if(class_exists($repositoryClass))
        {
            $repositoryInstance = new $repositoryClass($this, $metaInformation);

            if($repositoryInstance instanceof Repository)
            {
                return $repositoryInstance;
            }

            throw new \RuntimeException(sprintf(
                '%s must extends the FS\SolrBundle\Repository\Repository',
                $repositoryClass
            ));
        }

        return new Repository($this, $metaInformation);
    }

    /**
     * Run a query on Solr
     *
     * @param AbstractQuery $query
     *
     * @return Result
     */
    public function query(AbstractQuery $query)
    {
        $queryString = $query->getQuery();
        $solrQuery   = $this->solrClient->createSelect($query->getOptions());
        $solrQuery->setQuery($queryString);

        try
        {
            $response = $this->solrClient->select($solrQuery);
        }
        catch(\Exception $e)
        {
            $errorEvent = new ErrorEvent(null, null, null, 'query solr');
            $errorEvent->setException($e);

            $this->eventManager->dispatch(Events::ERROR, $errorEvent);

            return array();
        }

        $this->numberOfFoundDocuments = $response->getNumFound();
        if($this->numberOfFoundDocuments == 0)
        {
            return array();
        }

        return $response;
    }

    /**
     * @param object          $document
     * @param MetaInformation $meta
     */
    public function removeDocument($document, MetaInformation $meta)
    {
        $command = $this->commandFactory->get('all');
        $this->entityMapper->setMappingCommand($command);

        $deleteQuery = $meta->getIdentifier()->name.':'.$document->id;

        $event = new Event($this->solrClient, $document, $meta);
        $this->eventManager->dispatch(Events::PRE_DELETE, $event);

        try
        {
            $delete = $this->solrClient->createUpdate();
            $delete->addDeleteQuery($deleteQuery);
            $delete->addCommit();

            $this->solrClient->update($delete);
        }
        catch(\Exception $e)
        {
            $errorEvent = new ErrorEvent(null, $document, $meta, 'delete-document', $event);
            $errorEvent->setException($e);

            $this->eventManager->dispatch(Events::ERROR, $errorEvent);
        }

        $this->eventManager->dispatch(Events::POST_DELETE, $event);
    }

    /**
     * @param object          $document
     * @param MetaInformation $meta
     */
    public function addDocument($document, MetaInformation $meta)
    {
        $event = new Event($this->solrClient, $document, $meta);
        $this->eventManager->dispatch(Events::PRE_INSERT, $event);

        $this->addDocumentToIndex($document, $meta, $event);

        $this->eventManager->dispatch(Events::POST_INSERT, $event);
    }

    /**
     * Number of results found by query
     *
     * @return integer
     */
    public function getNumFound()
    {
        return $this->numberOfFoundDocuments;
    }

    /**
     * clears the whole index by using the query *:*
     */
    public function clearIndex()
    {
        $this->eventManager->dispatch(Events::PRE_CLEAR_INDEX, new Event($this->solrClient));

        try
        {
            $delete = $this->solrClient->createUpdate();
            $delete->addDeleteQuery('*:*');
            $delete->addCommit();

            $this->solrClient->update($delete);
        }
        catch(\Exception $e)
        {
            $errorEvent = new ErrorEvent(null, null, null, 'clear-index');
            $errorEvent->setException($e);

            $this->eventManager->dispatch(Events::ERROR, $errorEvent);
        }

        $this->eventManager->dispatch(Events::POST_CLEAR_INDEX, new Event($this->solrClient));
    }

    /**
     * @param object          $document
     * @param MetaInformation $meta
     *
     * @return bool
     */
    public function updateDocument($document, MetaInformation $meta = null)
    {

        $event = new Event($this->solrClient, $document, $meta);
        $this->eventManager->dispatch(Events::PRE_UPDATE, $event);

        $this->addDocumentToIndex($document, $meta, $event);

        $this->eventManager->dispatch(Events::POST_UPDATE, $event);

        return true;
    }

    /**
     * @param Document        $doc
     * @param MetaInformation $meta
     * @param Event           $event
     */
    private function addDocumentToIndex($doc, MetaInformation $meta, Event $event)
    {
        try
        {
            $update = $this->solrClient->createUpdate();
            $update->addDocument($doc);
            $update->addCommit();

            $this->solrClient->update($update);
        }
        catch(\Exception $e)
        {
            $errorEvent = new ErrorEvent(null, $doc, $meta, json_encode($this->solrClient->getOptions()), $event);
            $errorEvent->setException($e);
            echo $e->getMessage();
            $this->eventManager->dispatch(Events::ERROR, $errorEvent);
        }
    }
}
