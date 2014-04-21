<?php

namespace FS\Console;


use FS\SolrBundle\Console\ConsoleResultFactory;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use FS\SolrBundle\Event\ErrorEvent;
use FS\SolrBundle\Event\Event;
use FS\SolrBundle\Tests\Doctrine\Mapper\ValidTestEntity;
use FS\SolrBundle\Tests\Util\MetaTestInformationFactory;

class ConsoleResultFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function resultFromErrorEventContainsExceptionMessage()
    {
        $error = new ErrorEvent();
        $error->setException(new \Exception('message'));

        $factory = new ConsoleResultFactory();
        $result = $factory->fromEvent($error);

        $this->assertEquals('message', $result->getMessage());
    }

    /**
     * @test
     */
    public function resultNotContainsIdAndEntityWhenMetaInformationNull()
    {
        $event = new Event(null, null, null, '');

        $factory = new ConsoleResultFactory();
        $result = $factory->fromEvent($event);

        $this->assertEquals(null, $result->getResultId());
        $this->assertEquals('', $result->getEntity());
        $this->assertEquals('', $result->getMessage());
    }

    /**
     * @test
     */
    public function resultFromSuccessEventContainsNoMessage()
    {
        $entity = MetaTestInformationFactory::getEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $event = new Event(null, $entity, $meta, '');

        $factory = new ConsoleResultFactory();
        $result = $factory->fromEvent($event);

        $this->assertEquals(2, $result->getResultId());
        $this->assertEquals(get_class($entity), $result->getEntity());
        $this->assertEquals('', $result->getMessage());
    }
}
