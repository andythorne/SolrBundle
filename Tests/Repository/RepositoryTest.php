<?php

namespace FS\SolrBundle\Tests\Solr\Repository;

use FS\SolrBundle\Tests\Util\MetaTestInformationFactory;
use FS\SolrBundle\Tests\Util\CommandFactoryStub;
use Solarium\QueryType\Update\Query\Document\Document;
use FS\SolrBundle\Repository\Repository;
use FS\SolrBundle\Tests\Doctrine\Mapper\ValidTestEntity;

/**
 * @group repository
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    public function testFind_DocumentIsKnown()
    {

        $entity = new ValidTestEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $solr =  $this->mockSolr($entity, $meta);
        $repo = new Repository($solr, $meta);

        $actual = $repo->find(2);

        $this->assertInstanceOf(get_class($entity), $actual, 'find return no entity');
    }

    public function testFindAll()
    {
        $entity = new ValidTestEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $solr =  $this->mockSolr($entity, $meta);
        $repo = new Repository($solr, $meta);

        $actual = $repo->findAll();

        $this->assertTrue(is_array($actual));
    }

    public function testFindBy()
    {
        $fields = array(
            'title' => 'foo',
            'text' => 'bar'
        );

        $entity = new ValidTestEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $solr = $this->mockSolr($entity, $meta);

        $repo = new Repository($solr, $meta);

        $found = $repo->findBy($fields);

        $this->assertTrue(is_array($found));
    }

    private function mockSolr($entity, $meta)
    {

        $metaFactory = $this->getMock(
            'FS\SolrBundle\Doctrine\Mapper\MetaInformationFactory',
            array(),
            array(),
            '',
            false
        );

        $metaFactory->expects($this->any())
                    ->method('loadInformation')
                    ->will($this->returnValue($meta));

        $mapper = $this->getMock('FS\SolrBundle\Doctrine\Mapper\EntityMapper', array('fromResponse'), array(), '', false);
        $mapper->expects($this->once())
               ->method('fromResponse')
               ->will($this->returnValue(array($entity)));

        $solr = $this->getMock('FS\SolrBundle\Solr', array(), array(), '', false);
        $solr->expects($this->exactly(2))
             ->method('getMapper')
             ->will($this->returnValue($mapper));

        $solr->expects($this->once())
             ->method('getCommandFactory')
             ->will($this->returnValue(CommandFactoryStub::getFactoryWithAllMappingCommand()));

        $solr->expects($this->any())
             ->method('getMetaFactory')
             ->will($this->returnValue($metaFactory));

        $query = $this->getMock('FS\SolrBundle\Query\SolrQuery', array(), array(), '', false);
        $query->expects($this->any())
              ->method('addSearchTerm');

        $solrResult = $this->getMock('\Solarium\QueryType\Select\Result\Result', array('getDocuments', 'count'), array(), '', false);
        $solrResult->expects($this->once())
                   ->method('getDocuments')
                   ->will($this->returnValue(array($entity)));
        $solrResult->expects($this->once())
                   ->method('count')
                   ->will($this->returnValue(1));

        $solr->expects($this->once())
             ->method('query')
             ->will($this->returnValue($solrResult));

        return $solr;
    }


}

