<?php
namespace FS\SolrBundle\Doctrine\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class EntityField extends Field
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
        foreach($this->properties as $property)
        {
            $values[] = $this->value->{"get{$property}"}();
        }

        return $values;
    }

}
