<?php

namespace FS\SolrBundle\Doctrine\Hydration\Exception;

use Exception;

class HydratorNotFoundException extends \RuntimeException
{
    public function __construct(Exception $previous = null)
    {
        parent::__construct("No hydrator was found", null, $previous);
    }

}
