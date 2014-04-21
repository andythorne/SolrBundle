<?php

namespace FS\SolrBundle\Tests\Solr;

use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\InvalidTestEntityFiltered;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityFiltered;
use FS\SolrBundle\Tests\Doctrine\Mapper\SolrDocumentStub;
use FS\SolrBundle\Tests\ResultFake;
use FS\SolrBundle\Tests\SolrResponseFake;
use FS\SolrBundle\Query\FindByDocumentNameQuery;
use FS\SolrBundle\Event\EventManager;
use FS\SolrBundle\Tests\SolrClientFake;
use FS\SolrBundle\Tests\Doctrine\Mapper\ValidTestEntity;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\EntityWithRepository;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use FS\SolrBundle\Tests\Util\MetaTestInformationFactory;
use FS\SolrBundle\Solr;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidEntityRepository;
use FS\SolrBundle\Tests\Util\CommandFactoryStub;
use FS\SolrBundle\Query\SolrQuery;
use Solarium\QueryType\Update\Query\Document\Document;

/**
 *
 * @group facade
 */
class SolrTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \FS\SolrBundle\Doctrine\Mapper\MetaInformationFactory
     */
    private $metaFactory = null;

    private $config = null;
    private $commandFactory = null;
    private $eventDispatcher = null;
    private $mapper = null;
    private $solrClientFake = null;

    public function setUp()
    {
        $this->metaFactory = $metaFactory = $this->getMock(
            'FS\SolrBundle\Doctrine\Mapper\MetaInformationFactory',
            array(),
            array(),
            '',
            false
        );
        $this->config = $this->getMock('FS\SolrBundle\SolrConnection', array(), array(), '', false);
        $this->commandFactory = CommandFactoryStub::getFactoryWithAllMappingCommand();
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher', array(), array(), '', false);
        $this->mapper = $this->getMock('FS\SolrBundle\Doctrine\Mapper\EntityMapper', array(), array(), '', false);

        $this->solrClientFake = $this->getMock('Solarium\Client', array(), array(), '', false);
    }

    private function assertUpdateQueryExecuted()
    {
        $updateQuery = $this->getMock('Solarium\QueryType\Update\Query\Query', array(), array(), '', false);
        $updateQuery->expects($this->once())
            ->method('addDocument');

        $updateQuery->expects($this->once())
            ->method('addCommit');

        $this->solrClientFake
            ->expects($this->once())
            ->method('createUpdate')
            ->will($this->returnValue($updateQuery));
    }

    private function assertUpdateQueryWasNotExecuted()
    {
        $updateQuery = $this->getMock('Solarium\QueryType\Update\Query\Query', array(), array(), '', false);
        $updateQuery->expects($this->never())
            ->method('addDocument');

        $updateQuery->expects($this->never())
            ->method('addCommit');

        $this->solrClientFake
            ->expects($this->never())
            ->method('createUpdate');
    }

    private function assertDeleteQueryWasExecuted()
    {
        $deleteQuery = $this->getMock('Solarium\QueryType\Update\Query\Query', array(), array(), '', false);
        $deleteQuery->expects($this->once())
            ->method('addDeleteQuery')
            ->with($this->isType('string'));

        $deleteQuery->expects($this->once())
            ->method('addCommit');

        $this->solrClientFake
            ->expects($this->once())
            ->method('createUpdate')
            ->will($this->returnValue($deleteQuery));

        $this->solrClientFake
            ->expects($this->once())
            ->method('update')
            ->with($deleteQuery);
    }

    private function setupMetaFactoryLoadOneCompleteInformation($entity = null)
    {
        if (null === $entity) {
            $entity = MetaTestInformationFactory::getEntity();
        }

        $metaInformation = MetaTestInformationFactory::getMetaInformation($entity);

        $this->metaFactory->expects($this->once())
            ->method('loadInformation')
            ->will($this->returnValue($metaInformation));
    }

    public function testGetRepository_UserdefinedRepository()
    {
        $e = new EntityWithRepository();
        $this->setupMetaFactoryLoadOneCompleteInformation($e);

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);
        $actual = $solr->getRepository('Tests:EntityWithRepository');

        $this->assertTrue($actual instanceof ValidEntityRepository);
    }

    public function testGetRepository_UserdefinedInvalidRepository()
    {
        $e = new EntityWithRepository();
        $this->setupMetaFactoryLoadOneCompleteInformation($e);

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);
        $solr->getRepository('Tests:EntityWithInvalidRepository');
    }

    public function testAddDocument()
    {
        $this->assertUpdateQueryExecuted();

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->mapOneDocument();

        $this->setupMetaFactoryLoadOneCompleteInformation();

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);

        $entity = new ValidTestEntity();
        $repo = $solr->getRepository(get_class($entity));
        $repo->insert($entity);
    }

    public function testUpdateDocument()
    {
        $this->assertUpdateQueryExecuted();

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->mapOneDocument();

        $this->setupMetaFactoryLoadOneCompleteInformation();

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);
        $entity = new ValidTestEntity();
        $repo = $solr->getRepository(get_class($entity));
        $repo->update($entity);
    }

    public function testRemoveDocument()
    {
        $this->assertDeleteQueryWasExecuted();

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->setupMetaFactoryLoadOneCompleteInformation();

        $this->mapper->expects($this->once())
            ->method('toDocument')
            ->will($this->returnValue(new DocumentStub()));

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);

        $entity = new ValidTestEntity();
        $repo = $solr->getRepository(get_class($entity));
        $repo->delete($entity);
    }

    public function testClearIndex()
    {
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->assertDeleteQueryWasExecuted();

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);
        $solr->clearIndex();
    }

    private function assertQueryWasExecuted($data = array())
    {
        $selectQuery = $this->getMock('Solarium\QueryType\Select\Query\Query', array(), array(), '', false);
        $selectQuery->expects($this->once())
            ->method('setQuery');

        $queryResult = new ResultFake($data);

        $this->solrClientFake
            ->expects($this->once())
            ->method('createSelect')
            ->will($this->returnValue($selectQuery));

        $this->solrClientFake
            ->expects($this->once())
            ->method('select')
            ->with($selectQuery)
            ->will($this->returnValue($queryResult));
    }

    public function testQuery_NoResponseKeyInResponseSet()
    {
        $this->assertQueryWasExecuted();

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);

        $document = new Document();
        $document->addField('document_name_s', 'name');
        $query = new SolrQuery();
        $query->setDocument($document);

        $entities = $solr->query($query);
        $this->assertEquals(0, count($entities));
    }

    public function testQuery_OneDocumentFound()
    {
        $arrayObj = new SolrDocumentStub(array('title_s' => 'title'));

        $this->assertQueryWasExecuted(array($arrayObj));

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);

        $document = new Document();
        $document->addField('document_name_s', 'name');
        $query = new SolrQuery();
        $query->setDocument($document);


        $entities = $solr->query($query);
        $this->assertEquals(1, count($entities));
    }

    public function testAddEntity_ShouldNotIndexEntity()
    {
        $this->assertUpdateQueryWasNotExecuted();

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $entity = new ValidTestEntityFiltered();
        $this->setupMetaFactoryLoadOneCompleteInformation($entity);

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);

        $entity = new ValidTestEntityFiltered();
        $repo = $solr->getRepository(get_class($entity));
        $repo->insert($entity);

        $this->assertTrue($entity->getShouldBeIndexedWasCalled(), 'filter method was not called');
    }

    public function testAddEntity_ShouldIndexEntity()
    {
        $this->assertUpdateQueryExecuted();

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $entity = new ValidTestEntityFiltered();
        $entity->shouldIndex = true;
        $this->setupMetaFactoryLoadOneCompleteInformation($entity);

        $this->mapOneDocument();

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);
        $repo = $solr->getRepository(get_class($entity));
        $repo->insert($entity);

        $this->assertTrue($entity->getShouldBeIndexedWasCalled(), 'filter method was not called');
    }

    public function testAddEntity_FilteredEntityWithUnknownCallback()
    {
        $this->assertUpdateQueryWasNotExecuted();

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $entity = new ValidTestEntityFiltered();
        $entity->shouldIndex = false;
        $this->setupMetaFactoryLoadOneCompleteInformation($entity);

        $solr = new Solr($this->solrClientFake, $this->commandFactory, $this->eventDispatcher, $this->metaFactory, $this->mapper);
        try {
            $repo = $solr->getRepository(get_class($entity));
            $repo->insert($entity);
        } catch (\BadMethodCallException $e) {
            $this->assertTrue(true);
        }
    }

    private function mapOneDocument()
    {
        $this->mapper->expects($this->once())
            ->method('toDocument')
            ->will($this->returnValue($this->getMock('Solarium\QueryType\Update\Query\Document\DocumentInterface')));
    }
}

class DocumentStub implements \Solarium\QueryType\Update\Query\Document\DocumentInterface
{
    public $id = 1;
    public $document_name_s = 'stub_document';

    /**
     * Constructor
     *
     * @param array $fields
     * @param array $boosts
     * @param array $modifiers
     */
    public function __construct(array $fields = array(), array $boosts = array(), array $modifiers = array())
    {

    }
}

