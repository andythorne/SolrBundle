<?php
namespace FS\SolrBundle\Doctrine\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class MetaFields extends Field
{
    public $fields = array();
}
