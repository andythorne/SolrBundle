<?php
namespace FS\SolrBundle\Doctrine\Mapper\Mapping;

use FS\SolrBundle\Doctrine\Annotation\Field;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;
use Solarium\QueryType\Update\Query\Document\Document;

/**
 * command maps all fields of the entity
 *
 * uses parent method for mapping of document_name and id
 */
class MapAllFieldsCommand extends AbstractDocumentCommand
{

    /**
     * @inheritdoc
     */
    public function createDocument($entity, MetaInformation $meta)
    {
        $fields = $meta->getFields();
        if (count($fields) == 0) {
            return null;
        }

        $document = new Document();
        $document->addField('id', $entity->getId());
        $document->setBoost($meta->getBoost());

        $valueMapping = $meta->extractSolrValues($entity);

        foreach ($fields as $field) {
            if ($field instanceof Field) {
                $document->addField($field->getNameWithAlias(), $valueMapping[$field->name], $field->getBoost());
            }
        }

        return $document;
    }
}
