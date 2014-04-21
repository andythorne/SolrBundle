<?php
namespace FS\SolrBundle\Doctrine\Mapper\Mapping;

use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use Solarium\QueryType\Update\Query\Document\Document;

/**
 * maps the common fields id and document_name
 */
abstract class AbstractDocumentCommand
{

    /**
     * @param object          $entity
     * @param MetaInformation $meta
     *
     * @return Document
     */
    abstract public function createDocument($entity, MetaInformation $meta);

}
