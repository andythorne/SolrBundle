<?php
namespace FS\SolrBundle\Doctrine\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class CollectionField extends Field
{

    /**
     * @var string
     */
    public $properties = array();

    /**
     * @return string
     */
    public function getValue()
    {
        if(!$this->value)
            return null;

        $values = array();
        foreach($this->value as $entity)
        {
            foreach($this->properties as $name=>$property)
            {
                $values[] = $entity->{"get{$property}"}();
            }
        }

        return $values;
    }
}
