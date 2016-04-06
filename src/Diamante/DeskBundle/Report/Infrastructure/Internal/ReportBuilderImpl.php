<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */

namespace Diamante\DeskBundle\Report\Infrastructure\Internal;

use Diamante\DeskBundle\Report\ChartTypeProvider;
use Diamante\DeskBundle\Report\Infrastructure\ReportBuilder;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Query\QueryException;


/**
 * Class ReportServiceImpl
 * @package Diamante\DeskBundle\Report\Api\Internal
 */
class ReportBuilderImpl implements ReportBuilder
{
    const TYPE_DQL = 'dql';

    const TYPE_REPOSITORY = 'repository';

    const METHOD_PREFIX = 'buildFrom';

    /**
     * @var Registry
     */
    protected $doctrineRegistry;

    /**
     * @var ChartTypeProvider
     */
    protected $chartTypeProvider;

    public function __construct(
        Registry $doctrineRegistry,
        ChartTypeProvider $chartTypeProvider
    ) {
        $this->doctrineRegistry = $doctrineRegistry;
        $this->chartTypeProvider = $chartTypeProvider;
    }

    /**
     * @param $config
     * @param $reportId
     * @return mixed
     */
    public function build($config, $reportId)
    {

        $method = $this->resolveSourceResultMethod($config['source'], $reportId);

        if (method_exists($this, $method)) {
            $result = call_user_func_array([$this, $method], [$config['source']]);
        } else {
            throw new \RuntimeException();
        }

        if (empty($result)) {
            return [];
        }

        $chart = $this->chartTypeProvider->getChartTypeObject($config['chart']['type']);
        return $chart->extractData($result, $config);

    }

    /**
     * Retrieve method name for getting results
     *
     * @param $config
     * @param $reportId
     * @return string
     */
    private function resolveSourceResultMethod($config, $reportId)
    {

        if (!isset($config['type'])) {
            $config['type'] = self::TYPE_DQL;
        }

        if ($config['type'] == self::TYPE_DQL) {
            if (!isset($config['dql'])) {
                $message = sprintf("Parameter 'dql' is not defined in source for report %s", $reportId);
                throw new \RuntimeException($message);
            }
            $method = self::METHOD_PREFIX . ucfirst(self::TYPE_DQL);
        }

        if ($config['type'] == self::TYPE_REPOSITORY) {
            if (!isset($config['repository'])) {
                $message = sprintf("Parameter 'repository' is not defined in source for report %s", $reportId);
                throw new \RuntimeException($message);
            }
            $method = self::METHOD_PREFIX . ucfirst(self::TYPE_REPOSITORY);
        }

        if (!isset($method)) {
            $message = sprintf("Unknown source type %s for report %s", $config['type'], $reportId);
            throw new \RuntimeException($message);
        }

        return $method;
    }

    /**
     * @param $config
     * @return array|mixed
     */
    protected function buildFromDql($config)
    {

        $query = $this->doctrineRegistry->getManager()->createQuery($config['dql']);
        try {
            $result = $query->execute();
        } catch (QueryException $e) {
            return [];
        }

        return $result;
    }

    /**
     * @param $config
     * @return array
     */
    protected function buildFromRepository($config)
    {
        if (!strpos($config['repository'], '::')) {
            throw new \RuntimeException('Action in repository is not defined');
        }

        list($class, $action) = explode('::', $config['repository']);
        if (!is_callable(array($class, $action))) {
            throw new \RuntimeException('Repository or action not found');
        }

        $repository = new $class($this->doctrineRegistry);

        return $repository->$action();
    }


}