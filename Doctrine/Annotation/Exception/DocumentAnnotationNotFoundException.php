<?php

namespace FS\SolrBundle\Doctrine\Annotation\Exception;

use Exception;

class DocumentAnnotationNotFoundException extends \RuntimeException
{
    /**
     * @param string    $className
     * @param Exception $previous
     */
    public function __construct($className, Exception $previous = null)
    {
        parent::__construct("No declaration for document found in entity {$className}", null, $previous);
    }

} 
