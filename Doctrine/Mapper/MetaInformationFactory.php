<?php
namespace FS\SolrBundle\Doctrine\Mapper;

use FS\SolrBundle\Doctrine\Annotation\AnnotationReader;
use FS\SolrBundle\Doctrine\ClassnameResolver\ClassnameResolver;
use FS\SolrBundle\Doctrine\Configuration;

/**
 *
 * @author fs
 *
 */
class MetaInformationFactory
{

    /**
     * @var MetaInformation
     */
    private $metaInformations = null;

    /**
     * @var AnnotationReader
     */
    private $annotationReader = null;

    /**
     * @var ClassnameResolver
     */
    private $classnameResolver = null;

    public function __construct()
    {
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * @param ClassnameResolver $classnameResolver
     */
    public function setClassnameResolver(ClassnameResolver $classnameResolver)
    {
        $this->classnameResolver = $classnameResolver;
    }

    /**
     * @param string|object entityAlias
     *
     * @throws \RuntimeException
     * @return MetaInformation
     */
    public function loadInformation($entity)
    {
        $className = $this->getClass($entity);

        $annotations = $this->annotationReader->parse($className);

        $metaInformation = new MetaInformation($className, $annotations);
        $metaInformation->setDocumentName($this->getDocumentName($className));

        return $metaInformation;
    }

    /**
     * @param object $entity
     * @throws \RuntimeException
     * @return string
     */
    private function getClass($entity)
    {
        if (is_object($entity)) {
            return get_class($entity);
        }

        if (class_exists($entity)) {
            return $entity;
        }

        $realClassName = $this->classnameResolver->resolveFullQualifiedClassname($entity);

        return $realClassName;
    }

    /**
     * @param string $fullClassName
     * @return string
     */
    private function getDocumentName($fullClassName)
    {
        $className = substr($fullClassName, (strrpos($fullClassName, '\\') + 1));

        return strtolower($className);
    }
}
