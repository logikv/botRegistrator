<?php

namespace MvcBox\SqlQuery;

use Exception;
use PDO;
use PDOStatement;

abstract class SqlQueryAbstract
{
    const QUERY_TYPE_SELECT = 1;
    const QUERY_TYPE_INSERT = 2;
    const QUERY_TYPE_UPDATE = 3;
    const QUERY_TYPE_DELETE = 4;
    const QUERY_TYPE_TRUNCATE = 5;
    const QUERY_TYPE_RAW = 6;

    /**
     * @var PDO
     */
    public $pdo;

    /**
     * @var PDOStatement
     */
    public $pdoStatement;

    /**
     * @var int
     */
    public $queryType;

    /**
     * @var array
     */
    public $values = array();

    /**
     * @var array
     */
    public $elements = array();

    /**
     * SqlQueryAbstract constructor.
     * @param string|PDO|null $dsnOrPdo
     * @param string|null $username
     * @param string|null $password
     * @param array $attr
     */
    public function __construct($dsnOrPdo = null, $username = null, $password = null, array $attr = array())
    {
        if ($dsnOrPdo instanceof PDO) {
            $this->pdo = $dsnOrPdo;
        } elseif (null !== $dsnOrPdo) {
            $this->pdo = new PDO($dsnOrPdo, $username, $password, $attr);
        }
    }

    /**
     * @return array
     */
    abstract public function queryTemplates();

    /**
     * @return string
     */
    abstract public function escapeSymbol();

    /**
     * @return string
     */
    public function getQueryTemplate()
    {
        $templates = $this->queryTemplates();
        return $templates[$this->queryType];
    }

    /**
     * @param string|null $template
     * @return mixed|null
     */
    public function buildQuery($template = null)
    {
        if (null === $template) {
            $template = $this->getQueryTemplate();
        }
        preg_match_all('/\\{\\{(.*?)\\}\\}/s', $template, $matches);
        foreach ($matches[1] as $key => $element) {
            $pattern = '/\\{\\%' . $element . '\\%\\}(.*?)\\{\\%\\/' . $element . '\\%\\}/s';
            if (isset($this->elements[$element])) {
                $template = preg_replace($pattern, '$1', $template);
                $template = str_replace($matches[0][$key], $this->elements[$element], $template);
            } else {
                $template = preg_replace($pattern, '', $template);
                $template = str_replace($matches[0][$key], '', $template);
            }
        }
        return rtrim($template);
    }

    /**
     * @param string $query
     * @return mixed
     */
    public function getQueryParams($query)
    {
        if (self::QUERY_TYPE_RAW === $this->queryType) {
            return $this->values;
        }
        foreach ($this->values as $key => $value) {
            if (
                false === ($pos = strpos($query, $key)) ||
                preg_match_all('/(?<!\\\\)\\\'/s', substr($query, 0, $pos), $matches) % 2 != 0
            ) {
                unset($this->values[$key]);
            }
        }
        return $this->values;
    }

    /**
     * @param string|int $index
     * @param string|int $value
     * @param string $separator
     * @param bool $add
     * @return $this
     */
    public function setElement($index, $value, $separator = '', $add = true)
    {
        if ($add && isset($this->elements[$index]) && '' != $this->elements[$index]) {
            $this->elements[$index] .= $separator . $value;
        } else {
            $this->elements[$index] = $value;
        }
        return $this;
    }

    /**
     * @param string|int $index
     * @return mixed
     */
    public function getElement($index)
    {
        if (isset($this->elements[$index])) {
            return $this->elements[$index];
        }
    }

    /**
     * @param string|int $index
     * @return bool
     */
    public function existsElement($index)
    {
        return isset($this->elements[$index]);
    }

    /**
     * @param string|int $index
     * @return $this
     */
    public function removeElement($index)
    {
        unset($this->elements[$index]);
        return $this;
    }

    /**
     * @param string $value
     * @param bool $escape
     * @return array|string
     */
    public function escape($value, $escape = true)
    {
        if ($this->isForMerge($value)) {
            return $this->merge($value);
        }
        $symbol = $this->escapeSymbol();
        if (!$escape) {
            return $value;
        } elseif (is_array($value)) {
            return array_map(array($this, 'escape'), $value);
        } elseif (($value = trim($value, ' ' . $symbol)) === '*') {
            return '*';
        }
        $value = explode('.', $value);
        $value = array_map(function ($input) use ($symbol) {
            $input = trim($input, ' ' . $symbol);
            return '*' === $input ? '*' : $symbol . $input . $symbol;
        }, $value);
        return implode('.', $value);
    }

    /**
     * @param string $expression
     * @param bool $prepare
     * @return string
     */
    public function exprPrepare($expression, $prepare = true)
    {
        if (!$prepare) {
            return is_array($expression) ? implode(', ', $expression) : $expression;
        }
        $expression = is_array($expression) ? $expression : array($expression);
        $result = array();
        foreach ($expression as $key => $value) {
            $skipAddTable = $this->isForMerge($value);
            if (is_int($key)) {
                $value = $this->escape($value);
            } else {
                $value = $this->escape($value) . ' AS ' . $this->escape($key);
            }
            if (!$skipAddTable && strpos($value, '.') === false && $this->existsElement('table')) {
                $value = self::QUERY_TYPE_SELECT === $this->queryType
                    ? $this->getElement('table_alias') . '.' . $value
                    : $this->getElement('table_real') . '.' . $value;
            }
            $result[] = $value;
        }
        return implode(', ', $result);
    }

    /**
     * @param string $table
     * @return string
     */
    public function tablePrepare($table)
    {
        $table = (array)$table;
        $result = array();
        foreach ($table as $key => $value) {
            if (is_int($key)) {
                $result[] = $this->escape($value);
            } else {
                $result[] = $this->escape($value) . ' ' . $this->escape($key);
            }
        }
        return implode(', ', $result);
    }

    /**
     * @param string|int $value
     * @param bool $bind
     * @return array|string
     */
    public function bindValue($value, $bind = true)
    {
        if ($this->isForMerge($value)) {
            return $this->merge($value);
        } elseif (!$bind) {
            return $value;
        } elseif (is_array($value)) {
            return array_map(array($this, 'bindValue'), $value);
        }
        static $counter = 0;
        $key = ':m' . ++$counter;
        $this->values[$key] = $value;
        return $key;
    }

    /**
     * @param string $query
     * @param mixed $params
     * @return SqlQueryAbstract
     */
    public function query($query, $params = array())
    {
        $this->queryType = self::QUERY_TYPE_RAW;
        $this->values = (array)$params;
        return $this->setElement('query', $query, null, false);
    }

    /**
     * @param string $table
     * @return $this
     */
    public function table($table)
    {
        $table = $this->tablePrepare($table);
        $this->setElement('table', $table, null, false);
        $table = current(explode(',', $table, 2));
        if (strpos($table, ' ') === false) {
            $this->setElement('table_real', $table, null, false)->setElement('table_alias', $table, null, false);
        } else {
            list ($real, $alias) = explode(' ', $table);
            $this->setElement('table_real', $real, null, false)->setElement('table_alias', $alias, null, false);
        }
        return $this;
    }

    /**
     * @param bool $distinct
     * @return SqlQueryAbstract
     */
    public function distinct($distinct = true)
    {
        if ($distinct) {
            return $this->setElement('distinct', '', null, false);
        }
        return $this->removeElement('distinct');
    }

    /**
     * @param string $expression
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function select($expression = '*', $prepare = true)
    {
        $this->queryType = self::QUERY_TYPE_SELECT;
        $expression = $this->exprPrepare($expression, $prepare);
        return $this->setElement('select', $expression, null, false);
    }

    /**
     * @param array $data
     * @param bool $multiInsert
     * @param bool $prepare
     * @param bool $run
     * @return bool|SqlQueryAbstract
     * @throws Exception
     */
    public function insert(array $data, $multiInsert = false, $prepare = true, $run = true)
    {
        $this->queryType = self::QUERY_TYPE_INSERT;
        if (!$multiInsert) {
            $data = array($data);
        }
        $this->setElement('insert_fields', $this->exprPrepare(array_keys(current($data))), null, false);
        $this->setElement('insert_params', null, null, false);
        foreach ($data as $item) {
            $this->setElement('insert_params', '(' . implode(', ', $this->bindValue($item, $prepare)) . ')', ', ');
        }
        return $run ? $this->run() : $this;
    }

    /**
     * @param array $data
     * @param bool $prepare
     * @param bool $run
     * @return bool|SqlQueryAbstract
     * @throws Exception
     */
    public function update(array $data, $prepare = true, $run = true)
    {
        $this->queryType = self::QUERY_TYPE_UPDATE;
        foreach ($data as $field => $value) {
            $this->setElement('update', $this->exprPrepare($field) . ' = ' . $this->bindValue($value, $prepare), ',');
        }
        return $run ? $this->run() : $this;
    }

    /**
     * @param bool $run
     * @return bool|SqlQueryAbstract
     * @throws Exception
     */
    public function delete($run = true)
    {
        $this->queryType = self::QUERY_TYPE_DELETE;
        return $run ? $this->run() : $this;
    }

    /**
     * @param bool $run
     * @return bool|SqlQueryAbstract
     * @throws Exception
     */
    public function truncate($run = true)
    {
        $this->queryType = self::QUERY_TYPE_TRUNCATE;
        return $run ? $this->run() : $this;
    }

    /**
     * @param string $type
     * @param array $conditions
     * @param string $concatCondition
     * @param bool $defaultPrepare
     * @return $this
     */
    public function condition($type, array $conditions, $concatCondition, $defaultPrepare = true)
    {
        if (empty($conditions)) {
            return $this;
        }
        $data = '';
        foreach ($conditions as $column => $value) {
            if (is_int($column)) {
                $column = $value[0];
                $compare = isset($value[1]) ? $value[1] : (is_array($value[2]) ? 'IN' : '=');
                $condSep = isset($value[3]) ? $value[3] : 'AND';
                $prepare = isset($value[4]) ? $value[4] : $defaultPrepare;
                $value = $value[2];
            } else {
                $compare = is_array($value) ? 'IN' : '=';
                $condSep = 'AND';
                $prepare = $defaultPrepare;
            }
            if (is_array($value)) {
                $value = '(' . implode(', ', $this->bindValue($value, $prepare)) . ')';
            } else {
                $value = $this->bindValue($value, $prepare);
            }

            $data .= '' === $data ? '(' : ' ' . $condSep . ' ';
            $data .= $this->exprPrepare($column, $prepare) . ' ' . $compare . ' ' . $value;
        }
        $this->setElement($type, $data . ')', ' ' . $concatCondition . ' ');
        return $this;
    }

    /**
     * @param array $conditions
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function where(array $conditions, $prepare = true)
    {
        return $this->andWhere($conditions, $prepare);
    }

    /**
     * @param array $conditions
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function andWhere(array $conditions, $prepare = true)
    {
        return $this->condition('where', $conditions, 'AND', $prepare);
    }

    /**
     * @param array $conditions
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function orWhere(array $conditions, $prepare = true)
    {
        return $this->condition('where', $conditions, 'OR', $prepare);
    }

    /**
     * @param array $conditions
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function having(array $conditions, $prepare = true)
    {
        return $this->andHaving($conditions, $prepare);
    }

    /**
     * @param array $conditions
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function andHaving(array $conditions, $prepare = true)
    {
        return $this->condition('having', $conditions, 'AND', $prepare);
    }

    /**
     * @param array $conditions
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function orHaving(array $conditions, $prepare = true)
    {
        return $this->condition('having', $conditions, 'OR', $prepare);
    }

    /**
     * @param string $params
     * @return SqlQueryAbstract
     */
    public function groupBy($params)
    {
        return $this->setElement('group_by', $this->exprPrepare($params), ', ');
    }

    /**
     * @param string|array $fields
     * @param string $sort
     * @return $this
     */
    public function orderBy($fields, $sort = 'ASC')
    {
        if (!is_array($fields)) {
            $fields = array($fields => $sort);
        }
        foreach ($fields as $field => $sort) {
            $this->setElement('order_by', $this->exprPrepare($field) . ' ' . $sort, ',');
        }
        return $this;
    }

    /**
     * @param string|array $fields
     * @return SqlQueryAbstract
     */
    public function orderByAsc($fields)
    {
        return $this->orderBy(array_fill_keys((array)$fields, 'ASC'));
    }

    /**
     * @param string|array $fields
     * @return SqlQueryAbstract
     */
    public function orderByDesc($fields)
    {
        return $this->orderBy(array_fill_keys((array)$fields, 'DESC'));
    }

    /**
     * @param string $joinTable
     * @param string $joinData
     * @param string $joinType
     * @return $this
     */
    public function joinRaw($joinTable, $joinData, $joinType)
    {
        $on = '' == $joinData ? '' : ' ON (' . $joinData . ')';
        return $this->setElement('join', $joinType . ' ' . $this->tablePrepare($joinTable) . $on, ' ');
    }

    /**
     * @param string $joinTable
     * @param mixed $param1
     * @param string|null $param2
     * @param string $compare
     * @param string $joinType
     * @return SqlQueryAbstract
     */
    public function join($joinTable, $param1, $param2 = null, $compare = '=', $joinType = '')
    {
        if (is_array($param1)) {
            foreach ($param1 as $key => &$item) {
                if (is_string($key)) {
                    $item = array($key, $item, $compare, 'AND');
                }
            }
            unset($item);
        } else {
            $param1 = array(
                array($param1, $param2, $compare, 'AND')
            );
        }
        $joinTable = $this->tablePrepare($joinTable);
        if (strpos($joinTable, ' ') === false) {
            $joinTableAlias = $joinTable;
        } else {
            list(, $joinTableAlias) = array_map('trim', explode(' ', $joinTable, 2));
        }
        $joinData = '';
        foreach ($param1 as $item) {
            if (count($item) == 2) {
                $item[] = '=';
            }
            if (count($item) == 3) {
                $item[] = 'AND';
            }

            list($leftExpr, $rightExpr, $compare, $condition) = $item;
            if (strpos($leftExpr, '.') === false) {
                $leftExpr = $this->escape($joinTableAlias . '.' . $leftExpr);
            } else {
                $leftExpr = $this->escape($leftExpr);
            }
            $rightExpr = $this->exprPrepare($rightExpr);
            $joinData .= ('' === $joinData ? '' : ' ' . $condition . ' ') . $leftExpr . ' ' . $compare . ' ' . $rightExpr;
        }
        return $this->joinRaw($joinTable, $joinData, $joinType);
    }

    /**
     * @param string $joinTable
     * @param string $joinData
     * @return SqlQueryAbstract
     */
    public function iJoinRaw($joinTable, $joinData)
    {
        return $this->joinRaw($joinTable, $joinData, 'INNER JOIN');
    }

    /**
     * @param string $joinTable
     * @param string $joinData
     * @return SqlQueryAbstract
     */
    public function loJoinRaw($joinTable, $joinData)
    {
        return $this->joinRaw($joinTable, $joinData, 'LEFT OUTER JOIN');
    }

    /**
     * @param string $joinTable
     * @param string $joinData
     * @return SqlQueryAbstract
     */
    public function roJoinRaw($joinTable, $joinData)
    {
        return $this->joinRaw($joinTable, $joinData, 'RIGHT OUTER JOIN');
    }

    /**
     * @param string $joinTable
     * @param string $joinData
     * @return SqlQueryAbstract
     */
    public function foJoinRaw($joinTable, $joinData)
    {
        return $this->joinRaw($joinTable, $joinData, 'FULL OUTER JOIN');
    }

    /**
     * @param string $joinTable
     * @return SqlQueryAbstract
     */
    public function cJoinRaw($joinTable)
    {
        return $this->joinRaw($joinTable, '', 'CROSS JOIN');
    }

    /**
     * @param string $joinTable
     * @param mixed $param1
     * @param string|null $param2
     * @param string $condition
     * @return SqlQueryAbstract
     */
    public function iJoin($joinTable, $param1, $param2 = null, $condition = '=')
    {
        return $this->join($joinTable, $param1, $param2, $condition, 'INNER JOIN');
    }

    /**
     * @param string $joinTable
     * @param mixed $param1
     * @param string|null $param2
     * @param string $condition
     * @return SqlQueryAbstract
     */
    public function loJoin($joinTable, $param1, $param2 = null, $condition = '=')
    {
        return $this->join($joinTable, $param1, $param2, $condition, 'LEFT OUTER JOIN');
    }

    /**
     * @param string $joinTable
     * @param mixed $param1
     * @param string|null $param2
     * @param string $condition
     * @return SqlQueryAbstract
     */
    public function roJoin($joinTable, $param1, $param2 = null, $condition = '=')
    {
        return $this->join($joinTable, $param1, $param2, $condition, 'RIGHT OUTER JOIN');
    }

    /**
     * @param string $joinTable
     * @param mixed $param1
     * @param string|null $param2
     * @param string $condition
     * @return SqlQueryAbstract
     */
    public function foJoin($joinTable, $param1, $param2 = null, $condition = '=')
    {
        return $this->join($joinTable, $param1, $param2, $condition, 'FULL OUTER JOIN');
    }

    /**
     * @param string $joinTable
     * @return SqlQueryAbstract
     */
    public function cJoin($joinTable)
    {
        return $this->joinRaw($joinTable, '', 'CROSS JOIN');
    }

    /**
     * @param int $limit
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function limit($limit, $prepare = false)
    {
        return $this->setElement('limit_count', $this->bindValue((int)$limit, $prepare), null, false);
    }

    /**
     * @param int $offset
     * @param bool $prepare
     * @return SqlQueryAbstract
     */
    public function offset($offset, $prepare = false)
    {
        return $this->setElement('limit_offset', $this->bindValue((int)$offset, $prepare), null, false);
    }

    /**
     * @param string $funcName
     * @param string|array $fields
     * @param bool $returnQuery
     * @return bool|int|SqlQueryAbstract|string
     */
    public function callFunction($funcName, $fields = '*', $returnQuery = false)
    {
        if ('*' !== $fields) {
            $fields = $this->exprPrepare($fields);
        }
        $distinct = '';
        if ('*' !== $fields && $this->existsElement('distinct')) {
            $this->removeElement('distinct');
            $distinct = 'DISTINCT ';
        }
        $query = $this->select(strtoupper($funcName) . '(' . $distinct . $fields . ')', false);
        return $returnQuery ? $query : $query->scalar();
    }

    /**
     * @param string $fields
     * @param bool $returnQuery
     * @return bool|int|SqlQueryAbstract|string
     */
    public function fCount($fields = '*', $returnQuery = false)
    {
        return $this->callFunction('COUNT', $fields, $returnQuery);
    }

    /**
     * @param string $fields
     * @param bool $returnQuery
     * @return bool|int|SqlQueryAbstract|string
     */
    public function fMax($fields = '*', $returnQuery = false)
    {
        return $this->callFunction('MAX', $fields, $returnQuery);
    }

    /**
     * @param string $fields
     * @param bool $returnQuery
     * @return bool|int|SqlQueryAbstract|string
     */
    public function fMin($fields = '*', $returnQuery = false)
    {
        return $this->callFunction('MIN', $fields, $returnQuery);
    }

    /**
     * @param string $fields
     * @param bool $returnQuery
     * @return bool|int|SqlQueryAbstract|string
     */
    public function fSum($fields = '*', $returnQuery = false)
    {
        return $this->callFunction('SUM', $fields, $returnQuery);
    }

    /**
     * @param string $fields
     * @param bool $returnQuery
     * @return bool|int|SqlQueryAbstract|string
     */
    public function fAvg($fields = '*', $returnQuery = false)
    {
        return $this->callFunction('AVG', $fields, $returnQuery);
    }

    /**
     * @param string $fields
     * @param bool $returnQuery
     * @return bool|int|SqlQueryAbstract|string
     */
    public function fFirst($fields = '*', $returnQuery = false)
    {
        return $this->callFunction('FIRST', $fields, $returnQuery);
    }

    /**
     * @param string $fields
     * @param bool $returnQuery
     * @return bool|int|SqlQueryAbstract|string
     */
    public function fLast($fields = '*', $returnQuery = false)
    {
        return $this->callFunction('LAST', $fields, $returnQuery);
    }

    /**
     * @return bool
     */
    public function transactBegin()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * @return bool
     */
    public function transactIn()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * @return bool
     */
    public function transactCommit()
    {
        return $this->pdo->commit();
    }

    /**
     * @return bool
     */
    public function transactRollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * @param mixed $name
     * @return string
     */
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function merge($value)
    {
        if ($value instanceof self) {
            $this->values += $value->values;
            if (null === $value->queryType) {
                $value->select();
            }
            return '(' . $value->buildQuery() . ')';
        } elseif ($value instanceof Expression) {
            $this->values += $value->params;
            return $value->query;
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isForMerge($value)
    {
        return $value instanceof self || $value instanceof Expression;
    }

    /**
     * @param string $query
     * @param array $params
     * @return Expression
     */
    public function expr($query, array $params = array())
    {
        return new Expression($query, $params);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function run()
    {
        if (null === $this->queryType) {
            throw new Exception('Incorrect query type');
        }
        $query = $this->buildQuery();
        $params = $this->getQueryParams($query);
        $this->reset();
        if (!$this->pdo instanceof PDO) {
            return false;
        }
        $this->pdoStatement = $this->pdo->prepare($query);
        if ($this->pdoStatement instanceof PDOStatement) {
            return $this->pdoStatement->execute($params);
        }
        return false;
    }

    /**
     * @param int $fetchStyle
     * @param mixed $fetchArgument
     * @param array|null $ctorArgs
     * @return array|bool
     */
    public function all($fetchStyle = PDO::FETCH_ASSOC, $fetchArgument = null, $ctorArgs = null)
    {
        if (null === $this->queryType) {
            $this->select();
        }
        if (!$this->run()) {
            return false;
        }
        switch ($fetchStyle) {
            case PDO::FETCH_CLASS:
                return $this->pdoStatement->fetchAll($fetchStyle, $fetchArgument, (array)$ctorArgs);
            case PDO::FETCH_INTO:
            case PDO::FETCH_FUNC:
                return $this->pdoStatement->fetchAll($fetchStyle, $fetchArgument);
            default:
                return $this->pdoStatement->fetchAll($fetchStyle);
        }
    }

    /**
     * @param int $fetchStyle
     * @param mixed $fetchArgument
     * @param arrya|null $ctorArgs
     * @return bool|mixed|null
     */
    public function one($fetchStyle = PDO::FETCH_ASSOC, $fetchArgument = null, $ctorArgs = null)
    {
        $one = $this->limit(1)->all($fetchStyle, $fetchArgument, $ctorArgs);
        if (false === $one) {
            return false;
        }
        return empty($one) ? null : $one[0];
    }

    /**
     * @return string|int|bool
     */
    public function scalar()
    {
        $row = $this->one();
        return is_array($row) ? current($row) : false;
    }

    /**
     * @param string $column
     * @return mixed
     */
    public function column($column)
    {
        $row = $this->one();
        return isset($row[$column]) ? $row[$column] : false;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return $this->fCount() > 0;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return null !== $this->pdoStatement && $this->pdoStatement->errorInfo() !== array('00000', null, null);
    }

    /**
     * @return mixed
     */
    public function errorSqlState()
    {
        if ($this->isError()) {
            $error = $this->pdoStatement->errorInfo();
            return $error[0];
        }
    }

    /**
     * @return mixed
     */
    public function errorCode()
    {
        if ($this->isError()) {
            $error = $this->pdoStatement->errorInfo();
            return $error[1];
        }
    }

    /**
     * @return mixed
     */
    public function errorMsg()
    {
        if ($this->isError()) {
            $error = $this->pdoStatement->errorInfo();
            return $error[2];
        }
    }

    /**
     * @return string
     */
    public function errorQuery()
    {
        if ($this->isError()) {
            return $this->pdoStatement->queryString;
        }
    }

    /**
     * @return array
     */
    public function errorInfo()
    {
        return $this->pdoStatement->errorInfo();
    }

    /**
     *
     */
    public function reset()
    {
        $this->pdoStatement = $this->queryType = null;
        $this->values = $this->elements = array();
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->buildQuery();
    }

    /**
     * @return string
     */
    public function version()
    {
        return '0.3.1';
    }
}
