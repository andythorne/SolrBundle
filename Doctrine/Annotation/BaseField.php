<?php
namespace FS\SolrBundle\Doctrine\Annotation;

use Doctrine\Common\Annotations\Annotation;

abstract class BaseField extends Annotation
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $field;

    public $type;

    /**
     * @var array
     */
    private static $TYP_MAPPING = array(
        'string' => '_s',
        'text' => '_t',
        'date' => '_dt',
        'boolean' => '_b',
        'integer' => '_i',
        'long' => '_l',
        'float' => '_f',
        'double' => '_d',
    );

    /**
     * returns field name with type-suffix:
     *
     * eg: title_s
     *
     * @throws \RuntimeException
     * @return string
     */
    public function getNameWithAlias()
    {
        return $this->normalizeName($this->name) . $this->getTypeSuffix($this->type);
    }

    /**
     * @param string $type
     * @return string
     */
    private function getTypeSuffix($type)
    {
        if ($type == '') {
            return '';
        }

        if (!isset(self::$TYP_MAPPING[$this->type])) {
            return '';
        }

        return self::$TYP_MAPPING[$this->type];
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * normalize class attributes camelcased names to underscores
     * (according to solr specification, document field names should
     * contain only lowercase characters and underscores to maintain
     * retro compatibility with old components).
     *
     * @param $name The field name
     *
     * @return string normalized field name
     */
    protected function normalizeName($name)
    {
        $words = preg_split('/(?=[A-Z])/', $name);
        $words = array_map(
            function ($value) {
                return strtolower($value);
            },
            $words
        );

        return implode('_', $words);
    }

}
