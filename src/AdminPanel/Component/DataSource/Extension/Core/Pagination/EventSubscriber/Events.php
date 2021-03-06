<?php

declare(strict_types=1);

namespace AdminPanel\Component\DataSource\Extension\Core\Pagination\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AdminPanel\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Event\DataSourceEvent;
use AdminPanel\Component\DataSource\Extension\Core\Pagination\PaginationExtension;

/**
 * Class contains method called during DataSource events.
 */
class Events implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            DataSourceEvents::PRE_BIND_PARAMETERS => 'preBindParameters',
            DataSourceEvents::POST_GET_PARAMETERS => ['postGetParameters', -1024],
            DataSourceEvents::POST_BUILD_VIEW => 'postBuildView',
        ];
    }

    /**
     * Method called at PreBindParameters event.
     *
     * Sets proper page.
     *
     * @param \AdminPanel\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs $event
     */
    public function preBindParameters(\AdminPanel\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $parameters = $event->getParameters();

        $resultsPerPage = isset($parameters[$datasource->getName()][PaginationExtension::PARAMETER_MAX_RESULTS])
            ? (int) $parameters[$datasource->getName()][PaginationExtension::PARAMETER_MAX_RESULTS]
            : $datasource->getMaxResults();

        $datasource->setMaxResults($resultsPerPage);

        $page = isset($parameters[$datasource->getName()][PaginationExtension::PARAMETER_PAGE])
            ? (int) $parameters[$datasource->getName()][PaginationExtension::PARAMETER_PAGE]
            : 1;

        $datasource->setFirstResult(($page - 1) * $datasource->getMaxResults());
    }

    /**
     * @param \AdminPanel\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs $event
     */
    public function postGetParameters(\AdminPanel\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $datasourceName = $datasource->getName();

        $parameters = $event->getParameters();
        $maxresults = $datasource->getMaxResults();

        if ($maxresults) {
            $parameters[$datasourceName][PaginationExtension::PARAMETER_MAX_RESULTS] = $maxresults;
        }

        if ($maxresults == 0) {
            $page = 1;
        } else {
            $current = $datasource->getFirstResult();
            $page = (int) floor($current/$maxresults) + 1;
        }

        unset($parameters[$datasourceName][PaginationExtension::PARAMETER_PAGE]);
        if ($page > 1) {
            $parameters[$datasourceName][PaginationExtension::PARAMETER_PAGE] = $page;
        }

        $event->setParameters($parameters);
    }

    /**
     * Method called at PostBuildView event.
     *
     * @param \AdminPanel\Component\DataSource\Event\DataSourceEvent\ViewEventArgs $event
     */
    public function postBuildView(\AdminPanel\Component\DataSource\Event\DataSourceEvent\ViewEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $datasourceName = $datasource->getName();
        $view = $event->getView();
        $parameters = $view->getParameters();
        $maxresults = $datasource->getMaxResults();

        if ($maxresults == 0) {
            $all = 1;
            $page = 1;
        } else {
            $all = (int) ceil(count($datasource->getResult())/$maxresults);
            $current = $datasource->getFirstResult();
            $page = (int) floor($current/$maxresults) + 1;
        }

        unset($parameters[$datasourceName][PaginationExtension::PARAMETER_PAGE]);
        $pages = [];

        for ($i = 1; $i <= $all; $i++) {
            if ($i > 1) {
                $parameters[$datasourceName][PaginationExtension::PARAMETER_PAGE] = $i;
            }

            $pages[$i] = $parameters;
        }

        $view->setAttribute('max_results', $maxresults);
        $view->setAttribute('page', $page);
        $view->setAttribute('parameters_pages', $pages);
    }
}
