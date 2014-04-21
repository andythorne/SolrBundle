<?php

namespace FS\SolrBundle\Tests\Doctrine\Mapping\Mapper;

use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityNoBoost;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityNoTypes;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityFiltered;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityFloatBoost;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityNumericFields;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityWithInvalidBoost;
use FS\SolrBundle\Tests\Doctrine\Mapper\ValidTestEntity;
use FS\SolrBundle\Doctrine\Annotation\AnnotationReader;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\EntityWithRepository;
use FS\SolrBundle\Tests\Doctrine\Mapper\NotIndexedEntity;

/**
 *
 * @group annotation
 */
class AnnotationReaderTest extends \PHPUnit_Framework_TestCase
{
    public function textParse_Valid()
    {
        $reader = new AnnotationReader();

        $class = new EntityWithRepository();
        $annotations = $reader->parse($class);

        $this->assertEquals(5, count($annotations));

        $this->assertArrayHasKey('boost', $annotations, 'parse returned boost array');
        $this->assertArrayHasKey('fields', $annotations, 'parse returned fields array');
        $this->assertArrayHasKey('synchronization_callback', $annotations, 'parse returned synchronization array');
        $this->assertArrayHasKey('identifier', $annotations, 'parse returned identifier array');
        $this->assertArrayHasKey('repository', $annotations, 'parse returned repository array');

    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetFields_NoFieldsDected()
    {
        $reader = new AnnotationReader();

        $class = new NotIndexedEntity();
        $annotations = $reader->parse($class);

        $this->assertEquals(0, count($annotations));
    }

    public function testGetFields_ThreeFieldsDetected()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntity();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('fields', $annotations, 'parse returned fields array');
        $this->assertEquals(4, count($annotations['fields']), '4 fields are mapped');
    }

    public function testGetFields_OneFieldsOneTypes()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntityNoTypes();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('fields', $annotations, 'parse returned fields array');
        $this->assertEquals(1, count($annotations['fields']), '1 fields are mapped');

        $field = $annotations['fields']['title'];
        $this->assertInstanceOf('\FS\SolrBundle\Doctrine\Annotation\Field', $field);
        $this->assertEquals('title', $field->getNameWithAlias());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetIdentifier_ShouldThrowException()
    {
        $reader = new AnnotationReader();

        $class = new NotIndexedEntity();
        $reader->parse($class);
    }

    public function testGetIdentifier()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntity();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('identifier', $annotations, 'parse returned identifier array');
        $this->assertEquals($annotations['identifier'], 'id');
    }

    public function testGetRepository_ValidRepositoryDeclared()
    {
        $reader = new AnnotationReader();

        $class = new EntityWithRepository();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('repository', $annotations, 'parse returned repository array');

        $expected = 'FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidEntityRepository';
        $actual = $annotations['repository'];
        $this->assertEquals($expected, $actual, 'wrong declared repository');
    }

    public function testGetRepository_NoRepositoryAttributSet()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntity();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('repository', $annotations, 'parse returned repository array');

        $expected = '';
        $actual = $annotations['repository'];
        $this->assertEquals($expected, $actual, 'no repository was declared');
    }

    public function testGetBoost()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntity();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('boost', $annotations, 'parse returned boost array');

        $this->assertEquals(1, $annotations['boost']);
    }

    public function testGetBoost_BoostNotNumeric()
    {
        $reader = new AnnotationReader();

        try {

            $class = new ValidTestEntityWithInvalidBoost();
            $annotations = $reader->parse($class);

            $this->assertArrayHasKey('boost', $annotations, 'parse returned boost array');

            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals(
                'Invalid boost value aaaa for entity FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityWithInvalidBoost',
                $e->getMessage()
            );
        }
    }

    public function testGetBoost_BoostIsNumberic()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntityFloatBoost();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('boost', $annotations, 'parse returned boost array');

        $this->assertEquals(1.4, $annotations['boost']);
    }

    public function testGetBoost_BoostIsNull()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntityNoBoost();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('boost', $annotations, 'parse returned boost array');

        $this->assertEquals(null, $annotations['boost']);
    }

    public function testGetCallback_CallbackDefined()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntityFiltered();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('synchronization_callback', $annotations, 'parse returned synchronization_callback array');

        $this->assertEquals('shouldBeIndex', $annotations['synchronization_callback']);
    }

    public function testGetCallback_NoCallbackDefined()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntity();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('synchronization_callback', $annotations, 'parse returned synchronization_callback array');

        $this->assertEquals('', $annotations['synchronization_callback']);
    }

    /**
     * @test
     */
    public function numericFieldTypeAreSupported()
    {
        $reader = new AnnotationReader();

        $class = new ValidTestEntityNumericFields();
        $annotations = $reader->parse($class);

        $this->assertArrayHasKey('fields', $annotations, 'parse returned fields array');
        $this->assertEquals(4, count($annotations['fields']));

        $expectedFields = array('integer_i', 'double_d', 'float_f', 'long_l');
        $actualFields = array();
        foreach ($annotations['fields'] as $field) {
            $actualFields[] = $field->getNameWithAlias();
        }

        $this->assertEquals($expectedFields, $actualFields);
    }
}

