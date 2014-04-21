<?php

namespace FS\SolrBundle\Repository\Exception;


use Exception;

class RepositoryNotFoundException extends \RuntimeException
{
    public function __construct($class, Exception $previous = null)
    {
        parent::__construct("The Repository for {$class} does not exist", null, $previous);
    }

} 
