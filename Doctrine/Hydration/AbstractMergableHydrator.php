<?php

namespace FS\SolrBundle\Doctrine\Hydration;

use Doctrine\ORM\Query;

abstract class AbstractMergableHydrator
{

    /**
     * @param object|array    $targetEntity
     * @param array           $document
     *
     * @return object|array
     */
    public function merge($targetEntity, $document)
    {

        if(is_object($targetEntity))
        {
            $reflectionClass = new \ReflectionClass($targetEntity);
            foreach($document as $property => $value)
            {
                try
                {
                    $classProperty = $reflectionClass->getProperty($this->removeFieldSuffix($property));
                }
                catch(\ReflectionException $e)
                {
                    try
                    {
                        $classProperty = $reflectionClass->getProperty(
                                                         $this->toCamelCase($this->removeFieldSuffix($property))
                        );
                    }
                    catch(\ReflectionException $e)
                    {
                        continue;
                    }
                }

                $classProperty->setAccessible(true);
                $classProperty->setValue($targetEntity, $value);
            }
        }
        elseif(is_array($targetEntity))
        {
            foreach($document as $property => $value)
            {
                if(array_key_exists($property, $targetEntity))
                {
                    $targetEntity[$property] = $value;
                }
            }
        }

        return $targetEntity;
    }

    /**
     * returns the clean fieldname without type-suffix
     *
     * eg: title_s => title
     *
     * @param string $property
     *
     * @return string
     */
    private function removeFieldSuffix($property)
    {
        if(($pos = strrpos($property, '_')) !== false)
        {
            return substr($property, 0, $pos);
        }

        return $property;
    }

    /**
     * returns field name camelcased if it has underlines
     *
     * eg: user_id => userId
     *
     * @param string $fieldname
     *
     * @return string
     */
    private function toCamelCase($fieldname)
    {
        $words       = str_replace('_', ' ', $fieldname);
        $words       = ucwords($words);
        $pascalCased = str_replace(' ', '', $words);

        return lcfirst($pascalCased);
    }
} 
