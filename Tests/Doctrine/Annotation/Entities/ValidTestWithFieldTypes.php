<?php
namespace FS\SolrBundle\Tests\Doctrine\Annotation\Entities;

use FS\SolrBundle\Doctrine\Annotation as Solr;

/**
 * @Solr\Document(boost="1")
 */
class ValidTestWithFieldTypes
{
    /**
     * @Solr\Id
     */
    private $id;

    /**
     *
     * @Solr\Field(type="string", boost="1.8")
     */
    private $title = 'A title';

    /**
     *
     * @Solr\Field(type="text")
     */
    private $text = 'A text';

    /**
     *
     * @Solr\Field(type="date", boost="1")
     */
    private $created_at = 'A created at';
}

