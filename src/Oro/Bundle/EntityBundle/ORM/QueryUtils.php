<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\DBAL\SQLParserUtils;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ParameterTypeInferer;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

class QueryUtils
{
    /**
     * @param Query $query
     * @param array $paramMappings
     *
     * @return array
     *
     * @throws QueryException
     */
    public static function processParameterMappings(Query $query, $paramMappings)
    {
        $sqlParams = [];
        $types     = [];

        /** @var Parameter $parameter */
        foreach ($query->getParameters() as $parameter) {
            $key = $parameter->getName();

            if (!isset($paramMappings[$key])) {
                throw QueryException::unknownParameter($key);
            }

            $value = $query->processParameterValue($parameter->getValue());
            $type  = ($parameter->getValue() === $value)
                ? $parameter->getType()
                : ParameterTypeInferer::inferType($value);

            foreach ($paramMappings[$key] as $position) {
                $types[$position] = $type;
            }

            $sqlPositions = $paramMappings[$key];
            $value        = [$value];
            $countValue   = count($value);

            for ($i = 0, $l = count($sqlPositions); $i < $l; $i++) {
                $sqlParams[$sqlPositions[$i]] = $value[($i % $countValue)];
            }
        }

        if (count($sqlParams) !== count($types)) {
            throw QueryException::parameterTypeMismatch();
        }

        if ($sqlParams) {
            ksort($sqlParams);
            $sqlParams = array_values($sqlParams);

            ksort($types);
            $types = array_values($types);
        }

        return [$sqlParams, $types];
    }

    /**
     * @param ResultSetMapping $mapping
     * @param string           $alias
     *
     * @return string
     *
     * @throws QueryException
     */
    public static function getColumnNameByAlias(ResultSetMapping $mapping, $alias)
    {
        foreach ($mapping->scalarMappings as $key => $val) {
            if ($alias === $val) {
                return $key;
            }
        }

        throw new QueryException(sprintf('Unknown column alias: %s', $alias));
    }

    /**
     * Returns an expression in SELECT clause by its alias
     *
     * @param QueryBuilder $qb
     * @param string       $alias An alias of an expression in SELECT clause
     *
     * @return string|null
     */
    public static function getSelectExprByAlias(QueryBuilder $qb, $alias)
    {
        /** @var \Doctrine\ORM\Query\Expr\Select $selectPart */
        foreach ($qb->getDQLPart('select') as $selectPart) {
            foreach ($selectPart->getParts() as $part) {
                if (preg_match_all('#(\,\s*)*(?P<expr>.+?)\\s+AS\\s+(?P<alias>\\w+)#i', $part, $matches)) {
                    foreach ($matches['alias'] as $key => $val) {
                        if ($val === $alias) {
                            return $matches['expr'][$key];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param Query $query
     *
     * @return string
     *
     * @throws QueryException
     */
    public static function getExecutableSql(Query $query)
    {
        $parserResult = static::parseQuery($query);

        $sql = $parserResult->getSqlExecutor()->getSqlStatements();

        list($params, $types) = QueryUtils::processParameterMappings($query, $parserResult->getParameterMappings());
        list($sql, $params, $types) = SQLParserUtils::expandListParameters($sql, $params, $types);

        $paramPos = SQLParserUtils::getPlaceholderPositions($sql);
        for ($i = count($paramPos) - 1; $i >= 0; $i--) {
            $sql = substr_replace(
                $sql,
                $query->getEntityManager()->getConnection()->quote($params[$i], $types[$i]),
                $paramPos[$i],
                1
            );
        }

        return $sql;
    }

    /**
     * @param Query $query
     *
     * @return ParserResult
     */
    public static function parseQuery(Query $query)
    {
        $parser = new Query\Parser($query);

        return $parser->parse();
    }

    /**
     * Builds CONCAT(...) DQL expression
     *
     * @param string[] $parts
     *
     * @return string
     */
    public static function buildConcatExpr(array $parts)
    {
        $stack = [];
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $stack[] = count($stack) === 0
                ? $parts[$i]
                : sprintf('CONCAT(%s, %s)', $parts[$i], array_pop($stack));
        }

        if (empty($stack)) {
            return '';
        }

        return array_pop($stack);
    }
}
