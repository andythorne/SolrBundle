<?php

namespace FS\SolrBundle\Tests\Doctrine\Hydration;

use Doctrine\ORM\Query;
use FS\SolrBundle\Doctrine\Hydration\DoctrineHydrator;
use FS\SolrBundle\Doctrine\Mapper\MetaInformationFactory;
use FS\SolrBundle\Tests\Doctrine\Mapper\SolrDocumentStub;
use FS\SolrBundle\Tests\Doctrine\Mapper\ValidTestEntity;

/**
 * @group hydration
 */
class DoctrineHydratorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function foundEntityInDbReplacesEntityOldTargetEntity()
    {
        $fetchedFromDoctrine = new ValidTestEntity();

        $repository = $this->getMock('Doctrine\Common\Persistence\EntityRepository', null);

        $entity = new ValidTestEntity();

        $metainformations = new MetaInformationFactory();
        $metainformations = $metainformations->loadInformation($entity);

        $doctrineRegistry = $this->setupDoctrineRegistry($metainformations, $repository, $fetchedFromDoctrine);

        $obj = new SolrDocumentStub(array());
        $obj->id = 1;

        $doctrine = new DoctrineHydrator($doctrineRegistry);
        $hydratedDocument = $doctrine->hydrate(array($obj), $metainformations);

        $this->assertEquals($hydratedDocument, $fetchedFromDoctrine);
    }

    /**
     * @test
     */
    public function entityFromDbNotFoundShouldNotModifyMetainformations()
    {
        $fetchedFromDoctrine = new ValidTestEntity();

        $repository = $this->getMock('Doctrine\Common\Persistence\EntityRepository', null);

        $entity = new ValidTestEntity();

        $metainformations = new MetaInformationFactory();
        $metainformations = $metainformations->loadInformation($entity);
        $class = $metainformations->getClassName();

        $doctrineRegistry = $this->setupDoctrineRegistry($metainformations, $repository, $fetchedFromDoctrine);

        $obj = new SolrDocumentStub(array());
        $obj->id = 1;

        $doctrine = new DoctrineHydrator($doctrineRegistry);
        $hydratedDocument = $doctrine->hydrate($obj, $metainformations);

        $this->assertInstanceOf($class,$hydratedDocument); // shallow match

    }

    /**
     * @param      $metainformations
     * @param      $repository
     * @param null $entity
     *
     * @return mixed
     */
    private function setupDoctrineRegistry($metainformations, $repository, $entity=null)
    {
        $query = new fakeQuery($entity);

        $em = $this->getMock('Doctrine\ORM\EntityManager', null, array(), '', false);

        $qb = $this->getMock('Doctrine\ORM\QueryBuilder', array('getQuery'), array($em));
        $qb->expects($this->once())
           ->method('getQuery')
           ->will($this->returnValue($query));

        $manager = $this->getMock('Doctrine\Common\Persistence\EntityManager', array('getRepository', 'createQueryBuilder'));
        $manager->expects($this->any())
            ->method('getRepository')
            ->with($metainformations->getClassName())
            ->will($this->returnValue($repository));

        $manager->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $doctrineRegistry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $doctrineRegistry->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($manager));

        return $doctrineRegistry;
    }


}

class fakeQuery
{
    private $result;

    function __construct($result)
    {
        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }
}
