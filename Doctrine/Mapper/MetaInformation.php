<?php
namespace FS\SolrBundle\Doctrine\Mapper;

use FS\SolrBundle\Doctrine\Annotation\Field;
use FS\SolrBundle\Doctrine\Annotation\MetaFields;

class MetaInformation
{

    /**
     * @var string
     */
    private $identifier = '';

    /**
     * @var string
     */
    private $className = '';

    /**
     * @var string
     */
    private $documentName = '';

    /**
     * @var array
     */
    private $fields = array();

    /**
     * @var string
     */
    private $repository = '';

    /**
     * @var number
     */
    private $boost = 0;

    /**
     * @var string
     */
    private $synchronizationCallback = '';

    /**
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * Config array of annotations
     *
     * @param string $class
     * @param array  $annotations
     */
    function __construct($class, array $annotations)
    {
        $this->className  = $class;
        $this->reflection = new \ReflectionClass($class);

        $this->boost                   = $annotations['boost'];
        $this->fields                  = $annotations['fields'];
        $this->synchronizationCallback = $annotations['synchronization_callback'];
        $this->identifier              = $annotations['identifier'];
        $this->repository              = $annotations['repository'];
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getDocumentName()
    {
        return $this->documentName;
    }

    /**
     * @return \FS\SolrBundle\Doctrine\Annotation\Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param string $documentName
     */
    public function setDocumentName($documentName)
    {
        $this->documentName = $documentName;
    }

    /**
     * @param string $field
     *
     * @return boolean
     */
    public function hasField($field)
    {
        return array_key_exists($field, $this->fields);
    }

    /**
     * @param unknown_type $field
     *
     * @return Field|null
     */
    public function getField($field)
    {
        if(!$this->hasField($field))
        {
            return null;
        }

        return $this->fields[$field];
    }

    /**
     * @return number
     */
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * @return boolean
     */
    public function hasSynchronizationFilter()
    {
        if($this->synchronizationCallback == '')
        {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getSynchronizationCallback()
    {
        return $this->synchronizationCallback;
    }

    /**
     * Accepts an entity, and turns it into an array of values for the document
     *
     * @param $entity
     *
     * @return array
     */
    public function extractSolrValues($entity)
    {
        $entityVals = array();

        foreach($this->getFields() as $field)
        {
            // ignore meta fields
            if($this->reflection->hasProperty($field->field))
            {
                $prop = $this->reflection->getProperty($field->field);
                $prop->setAccessible(true);
                $field->value             = $prop->getValue($entity);
                $entityVals[$field->name] = $field->getValue();
            }
        }

        return $entityVals;
    }
}
