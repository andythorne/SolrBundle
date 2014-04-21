<?php
namespace FS\SolrBundle\Query;

use Doctrine\ORM\Query;
use FS\SolrBundle\Doctrine\Mapper\MetaInformation;

class SolrQuery extends AbstractQuery
{

    /**
     * @var array
     */
    private $searchTerms = array();

    /**
     * @var bool
     */
    private $useAndOperator = false;

    /**
     * @var bool
     */
    private $useWildcards = true;

    private $mappedFields = array();

    public function setEntityMeta(MetaInformation $entityMeta)
    {

        $fields = $entityMeta->getFields();

        foreach ($fields as $field) {
            $this->mappedFields[$field->name] = $field;
        }

        parent::setEntityMeta($entityMeta);
    }

    public function getMappedFields()
    {
        return $this->mappedFields;
    }



    /**
     * @param int $hydration
     *
     * @return array
     */
    public function getResult($hydration = Query::HYDRATE_OBJECT)
    {
        return $this->solr->query($this, $hydration);
    }

    /**
     * @param bool $strict
     */
    public function setUseAndOperator($strict)
    {
        $this->useAndOperator = $strict;
    }

    /**
     * @param bool $boolean
     */
    public function setUseWildcard($boolean)
    {
        $this->useWildcards = $boolean;
    }

    /**
     * @return array
     */
    public function getSearchTerms()
    {
        return $this->searchTerms;
    }

    /**
     * @param array|string $value
     */
    public function queryAllFields($value)
    {
        $this->setUseAndOperator(false);

        foreach($this->mappedFields as $name => $field)
        {
            $this->searchTerms[$field->getNameWithAlias()] = $value;
        }
    }

    /**
     *
     * @param string $fieldName
     * @param string $value
     * @return self
     */
    public function addSearchTerm($fieldName, $value)
    {
        if(array_key_exists($fieldName, $this->mappedFields))
        {
            $this->searchTerms[$this->mappedFields[$fieldName]->getNameWithAlias()] = $value;
        }

        return $this;
    }

    /**
     * @param string $fieldName
     *
     * @return SolrQuery
     */
    public function addField($fieldName)
    {
        if(array_key_exists($fieldName, $this->mappedFields))
        {
            parent::addField($this->mappedFields[$fieldName]->getNameWithAlias());
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        $term = '';
        if (count($this->searchTerms) == 0) {
            return $term;
        }

        $logicOperator = 'AND';
        if (!$this->useAndOperator) {
            $logicOperator = 'OR';
        }

        $termCount = 1;
        foreach ($this->searchTerms as $fieldName => $fieldValue) {

            if ($this->useWildcards) {
                $term .= $fieldName . ':*' . $fieldValue . '*';
            } else {
                $term .= $fieldName . ':' . $fieldValue;
            }

            if ($termCount < count($this->searchTerms)) {
                $term .= ' ' . $logicOperator . ' ';
            }

            $termCount++;
        }

        $this->setQuery($term);

        return $term;
    }

}
