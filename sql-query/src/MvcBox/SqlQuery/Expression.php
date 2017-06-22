<?php

namespace MvcBox\SqlQuery;

class Expression
{
    /**
     * @var string
     */
    public $query;

    /**
     * @var array
     */
    public $params;

    /**
     * Expression constructor.
     * @param string $query
     * @param array $params
     */
    public function __construct($query, array $params = array())
    {
        $this->query = $query;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->query;
    }
}
