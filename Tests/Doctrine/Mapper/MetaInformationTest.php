<?php
namespace FS\SolrBundle\Tests\Doctrine\Mapper;

use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use FS\SolrBundle\Tests\Doctrine\Annotation\Entities\ValidTestEntityFiltered;
use FS\SolrBundle\Tests\Util\MetaTestInformationFactory;

/**
 *
 * @group mapper
 */
class MetaInformationTest extends \PHPUnit_Framework_TestCase
{
    private function createFieldObject($name, $value)
    {
        $value = new \stdClass();
        $value->name = $name;
        $value->value = $value;

        return $value;
    }

    public function testHasField_FieldExists()
    {
        $entity = MetaTestInformationFactory::getEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $this->assertTrue($meta->hasField('text'), 'metainformation should have text');
    }

    public function testHasField_FieldNotExists()
    {
        $entity = MetaTestInformationFactory::getEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $this->assertFalse($meta->hasField('text2'), 'metainformation should have text2');
    }

    public function testSetFieldValue()
    {
        $value1 = $this->createFieldObject('field1', 'oldfieldvalue');
        $value2 = $this->createFieldObject('field2', true);

        $fields = array(
            'field1' => $value1,
            'field2' => $value2
        );

        $entity = MetaTestInformationFactory::getEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $expectedValue = 'newFieldValue';
        $entity->setText($expectedValue);

        $values = $meta->extractSolrValues($entity);

        $this->assertArrayHasKey('text', $values, 'values should have key text');
        $this->assertEquals($expectedValue, $values['text'], 'text should have new value');
    }

    public function testHasCallback_CallbackSet()
    {
        $entity = new ValidTestEntityFiltered();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $this->assertTrue($meta->hasSynchronizationFilter(), 'has callback');
    }

    public function testHasCallback_NoCallbackSet()
    {
        $entity = MetaTestInformationFactory::getEntity();
        $meta = MetaTestInformationFactory::getMetaInformation($entity);

        $this->assertFalse($meta->hasSynchronizationFilter(), 'has no callback');
    }
}

