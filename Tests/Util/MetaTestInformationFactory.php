<?php
namespace FS\SolrBundle\Tests\Util;

use FS\SolrBundle\Doctrine\Annotation\Field;
use FS\SolrBundle\Doctrine\ClassnameResolver\ClassnameResolver;
use FS\SolrBundle\Doctrine\ClassnameResolver\KnownNamespaceAliases;
use FS\SolrBundle\Doctrine\Mapper\MetaInformationFactory;
use FS\SolrBundle\Tests\Doctrine\Mapper\ValidTestEntity;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;

class MetaTestInformationFactory
{
    /**
     * Get a test entity
     *
     * @return ValidTestEntity
     */
    public static function getEntity()
    {
        $entity = new ValidTestEntity();
        $entity->setId(2);

        return $entity;
    }

    /**
     * @param $entity
     *
     * @return MetaInformation
     */
    public static function getMetaInformation($entity)
    {

        $factory = new MetaInformationFactory();
        $factory->setClassnameResolver(new ClassnameResolver(new KnownNamespaceAliases()));
        $metaInformation = $factory->loadInformation($entity);
        $metaInformation->setDocumentName('validtestentity');

        return $metaInformation;

    }
}

