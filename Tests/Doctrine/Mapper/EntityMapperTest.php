<?php

namespace FS\SolrBundle\Tests\Doctrine\Mapper;

use FS\SolrBundle\Doctrine\Annotation\AnnotationReader;
use FS\SolrBundle\Doctrine\Hydration\HydrationModes;
use FS\SolrBundle\Doctrine\Mapper\EntityMapper;
use FS\SolrBundle\Doctrine\Mapper\Mapping\MapAllFieldsCommand;
use FS\SolrBundle\Doctrine\Mapper\MetaInformationFactory;
use FS\SolrBundle\Tests\Util\MetaTestInformationFactory;
use Solarium\QueryType\Update\Query\Document\Document;

/**
 *
 * @group mapper
 */
class EntityMapperTest extends \PHPUnit_Framework_TestCase
{

    private $doctrineHydrator = null;
    private $indexHydrator = null;

    public function setUp()
    {
        $this->doctrineHydrator = $this->getMock('FS\SolrBundle\Doctrine\Hydration\DoctrineHydrator', array('hydrate'), array(), '', false);
        $this->indexHydrator = $this->getMock('FS\SolrBundle\Doctrine\Hydration\IndexHydrator', array('hydrate'));
    }

    public function testToDocument_EntityMayNotIndexed()
    {
        $mapper = new \FS\SolrBundle\Doctrine\Mapper\EntityMapper();
        $mapper->addHydrator($this->doctrineHydrator);
        $mapper->addHydrator($this->indexHydrator);

        $entity = MetaTestInformationFactory::getEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);
        $actual = $mapper->toDocument($entity, $meta);
        $this->assertNull($actual);
    }

    public function testToDocument_DocumentIsUpdated()
    {
        $mapper = new \FS\SolrBundle\Doctrine\Mapper\EntityMapper();
        $mapper->addHydrator($this->doctrineHydrator);
        $mapper->addHydrator($this->indexHydrator);
        $mapper->setMappingCommand(new MapAllFieldsCommand(new AnnotationReader()));

        $entity = MetaTestInformationFactory::getEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);
        $actual = $mapper->toDocument($entity, $meta);
        $this->assertTrue($actual instanceof Document);

        $this->assertNotNull($actual->id);
    }

    public function testToEntity_WithDocumentStub_HydrateIndexOnly()
    {
        $targetEntity = new ValidTestEntity();

        $this->indexHydrator->expects($this->once())
            ->method('hydrate')
            ->will($this->returnValue($targetEntity))
            ;

        $this->doctrineHydrator->expects($this->never())
            ->method('hydrate');

        $mapper = new \FS\SolrBundle\Doctrine\Mapper\EntityMapper();
        $mapper->addHydrator($this->doctrineHydrator);
        $mapper->addHydrator($this->indexHydrator);

        $mapper->setHydrationMode(HydrationModes::HYDRATE_INDEX);

        $metaInformationFactory = new MetaInformationFactory();
        $metaInformation = $metaInformationFactory->loadInformation($targetEntity);

        $entity = $mapper->fromResponse(array(new SolrDocumentStub()), $metaInformation);

        $this->assertTrue($entity instanceof $targetEntity);
    }

    public function testToEntity_ConcreteDocumentClass_WithDoctrine()
    {
        $targetEntity = new ValidTestEntity();

        $this->indexHydrator->expects($this->never())
            ->method('hydrate');

        $this->doctrineHydrator->expects($this->once())
            ->method('hydrate')
            ->will($this->returnValue($targetEntity));

        $mapper = new \FS\SolrBundle\Doctrine\Mapper\EntityMapper();
        $mapper->addHydrator($this->doctrineHydrator);
        $mapper->addHydrator($this->indexHydrator);
        $mapper->setHydrationMode(HydrationModes::HYDRATE_DOCTRINE);

        $metaInformationFactory = new MetaInformationFactory();
        $metaInformation = $metaInformationFactory->loadInformation($targetEntity);

        $entity = $mapper->fromResponse(array(new Document(array())), $metaInformation);

        $this->assertTrue($entity instanceof $targetEntity);
    }

    public function ToCamelCase()
    {
        $mapper = new EntityMapper();
        $mapper->addHydrator($this->doctrineHydrator);
        $mapper->addHydrator($this->indexHydrator);

        $meta = new \ReflectionClass($mapper);
        $method = $meta->getMethod('toCamelCase');
        $method->setAccessible(true);
        $calmelCased = $method->invoke($mapper, 'test_underline');
        $this->assertEquals('testUnderline', $calmelCased);
    }
}

