<?php
namespace FS\SolrBundle\Doctrine\Annotation;

use Doctrine\Common\Annotations\AnnotationReader as Reader;
use FS\SolrBundle\Doctrine\Annotation\Exception\DocumentAnnotationNotFoundException;

class AnnotationReader
{

    /**
     * @var Reader
     */
    private $reader;

    const DOCUMENT_CLASS = 'FS\SolrBundle\Doctrine\Annotation\Document';
    const FIELD_CLASS = 'FS\SolrBundle\Doctrine\Annotation\Field';
    const META_FIELD_CLASS = 'FS\SolrBundle\Doctrine\Annotation\MetaFields';
    const FIELD_IDENTIFIER_CLASS = 'FS\SolrBundle\Doctrine\Annotation\Id';
    const DOCUMENT_INDEX_CLASS = 'FS\SolrBundle\Doctrine\Annotation\Document';
    const SYNCHRONIZATION_FILTER_CLASS = 'FS\SolrBundle\Doctrine\Annotation\SynchronizationFilter';

    /**
     *
     */
    public function __construct()
    {
        $this->reader = new Reader();
    }


    /**
     * @param object|string $entity
     *
     * @return array
     */
    public function parse($entity)
    {
        $reflectionClass = new \ReflectionClass($entity);

        if(!$this->hasDocumentDeclaration($reflectionClass))
        {
            throw new DocumentAnnotationNotFoundException($reflectionClass->getName());
        }

        return array(
            'identifier'               => $this->getIdentifierAnnotations($reflectionClass),
            'fields'                   => $this->getFieldAnnotations($reflectionClass),
            'boost'                    => $this->getEntityBoostAnnotations($reflectionClass),
            'repository'               => $this->getRepositoryAnnotations($reflectionClass),
            'synchronization_callback' => $this->getSynchronizationCallback($reflectionClass),
        );


    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return boolean
     */
    public function hasDocumentDeclaration(\ReflectionClass $reflectionClass)
    {
        $annotation = $this->reader->getClassAnnotation($reflectionClass, self::DOCUMENT_INDEX_CLASS);

        return $annotation !== null;
    }

    /**
     * reads the entity and returns a set of annotations
     *
     * @param \ReflectionClass $reflectionClass
     * @param string $type
     * @return array
     */
    private function getPropertiesByType(\ReflectionClass $reflectionClass, $type)
    {
        $properties = $reflectionClass->getProperties();

        $fields = array();
        foreach ($properties as $property) {
            $annotation = $this->reader->getPropertyAnnotation($property, $type);

            if (null === $annotation) {
                continue;
            }

            $property->setAccessible(true);
            $annotation->field = $property->getName();
            $annotation->name = $annotation->name ?: $property->getName();

            $fields[] = $annotation;

        }

        return $fields;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getFieldAnnotations(\ReflectionClass $reflectionClass)
    {
        $fields = array();

        $definedFields = $this->getPropertiesByType($reflectionClass, self::FIELD_CLASS);
        foreach($definedFields as $field)
        {
            $fields[$field->name] = $field;
        }


        $metaFields = $this->reader->getClassAnnotation($reflectionClass, self::META_FIELD_CLASS);
        if($metaFields)
        {
            $fieldClass = self::FIELD_CLASS;
            foreach($metaFields->fields as $field)
            {
                /** @var Field $field */
                $newField = new $fieldClass($field);
                $fields[$newField->name] = $newField;
            }
        }

        return $fields;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @throws \InvalidArgumentException if the boost value is not numeric
     * @return number
     */
    private function getEntityBoostAnnotations(\ReflectionClass $reflectionClass)
    {
        $annotation = $this->reader->getClassAnnotation($reflectionClass, self::DOCUMENT_INDEX_CLASS);

        if (!$annotation instanceof Document) {
            return 0;
        }

        try {
            $boostValue = $annotation->getBoost();
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(sprintf($e->getMessage() . ' for entity %s', $reflectionClass->getName()));
        }

        return $boostValue;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return Type
     * @throws \RuntimeException
     */
    private function getIdentifierAnnotations(\ReflectionClass $reflectionClass)
    {
        $id = $this->getPropertiesByType($reflectionClass, self::FIELD_IDENTIFIER_CLASS);

        if (count($id) == 0) {
            throw new \RuntimeException('no identifer declared in entity ' . $reflectionClass->getName());
        }

        return reset($id);
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return string classname of repository
     */
    private function getRepositoryAnnotations(\ReflectionClass $reflectionClass)
    {
        $annotation = $this->reader->getClassAnnotation($reflectionClass, self::DOCUMENT_CLASS);

        if ($annotation instanceof Document) {
            return $annotation->repository;
        }

        return '';
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @return string
     */
    private function getSynchronizationCallback(\ReflectionClass $reflectionClass)
    {
        $annotation = $this->reader->getClassAnnotation($reflectionClass, self::SYNCHRONIZATION_FILTER_CLASS);

        if (!$annotation) {
            return '';
        }

        return $annotation->callback;
    }
}
