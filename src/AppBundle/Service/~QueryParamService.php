<?php

/**
 * Credits: https://github.com/buphmin/RookieDraft/tree/master/src/DraftBundle/Utils
 */

namespace AppBundle\Service;

use Doctrine\ORM\QueryBuilder;

class QueryParamService
{
    public function buildSelectQuery(
        QueryBuilder $queryBuilder,
        $parameters,
        $class,
        $sort = array(),
        $join = "",
        $joinParams = array()
    )
    {
        if (!empty($joinParams)) {
            foreach ($joinParams as $key => $joinParam) {
                unset($parameters[$key]);
            }
        }
        if (!empty($sort)) {
            unset($parameters[$sort['key']]);
        }

        $from = "AppBundle:" . $class;
        $queryBuilder
            ->select("c")
            ->from($from, "c");

        if (isset($parameters['limit'])) {
            $limit = $parameters['limit'];
            $queryBuilder->setMaxResults($limit);
            if (isset($parameters['page'])) {
                $page = ($parameters['page'] - 1) * $limit;
                $queryBuilder->setFirstResult($page);
                unset($parameters['page']);
            }
            unset($parameters['limit']);
        }

        $i = 0;
        foreach ($parameters as $key => $value) {
            if ($value === "") {
                continue;
            }
            if ($i == 0) {
                $queryBuilder
                    ->where("c.$key = :param$i")
                    ->setParameter(":param$i", "$value");
            } else {
                $queryBuilder
                    ->andWhere("c.$key = :param$i")
                    ->setParameter(":param$i", "$value");
            }
            $i++;
        }
        if (!empty($sort)) {
            $queryBuilder
                ->orderBy("c." . $sort['key'], $sort['value']);
        }

        return $queryBuilder;
    }
}