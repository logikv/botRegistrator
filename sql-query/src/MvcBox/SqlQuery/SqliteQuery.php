<?php

namespace MvcBox\SqlQuery;

class SqliteQuery extends SqlQueryAbstract
{
    /**
     * @return array
     */
    public function queryTemplates()
    {
        return array(
            self::QUERY_TYPE_SELECT => 'SELECT {%distinct%}DISTINCT {{distinct}}{%/distinct%}{{select}} FROM {{table}} {{join}} {%where%}WHERE {{where}}{%/where%} {%group_by%}GROUP BY {{group_by}} {%having%}HAVING {{having}}{%/having%}{%/group_by%} {%order_by%}ORDER BY {{order_by}}{%/order_by%} {%limit_count%}LIMIT {{limit_count}} {%limit_offset%}OFFSET {{limit_offset}}{%/limit_offset%}{%/limit_count%}',
            self::QUERY_TYPE_INSERT => 'INSERT INTO {{table_real}} ({{insert_fields}}) VALUES {{insert_params}}',
            self::QUERY_TYPE_UPDATE => 'UPDATE {{table_real}} SET {{update}} {%where%}WHERE {{where}}{%/where%}',
            self::QUERY_TYPE_DELETE => 'DELETE FROM {{table_real}} {%where%}WHERE {%where%}{%/where%}',
            self::QUERY_TYPE_TRUNCATE => 'DELETE FROM {{table_real}}',
            self::QUERY_TYPE_RAW => '{{query}}'
        );
    }

    /**
     * @return string
     */
    public function escapeSymbol()
    {
        return '"';
    }
}
