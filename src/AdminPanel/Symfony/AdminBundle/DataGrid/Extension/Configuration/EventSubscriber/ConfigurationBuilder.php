<?php

declare(strict_types=1);

namespace AdminPanel\Symfony\AdminBundle\DataGrid\Extension\Configuration\EventSubscriber;

use AdminPanel\Component\DataGrid\DataGridEventInterface;
use AdminPanel\Component\DataGrid\DataGridEvents;
use AdminPanel\Component\DataGrid\DataGridInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Parser;

class ConfigurationBuilder implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [DataGridEvents::PRE_SET_DATA => ['readConfiguration', 128]];
    }

    /**
     * {@inheritdoc}
     */
    public function readConfiguration(DataGridEventInterface $event)
    {
        $dataGrid = $event->getDataGrid();
        $dataGridConfiguration = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            if ($this->hasDataGridConfiguration($bundle->getPath(), $dataGrid->getName())) {
                $configuration = $this->getDataGridConfiguration($bundle->getPath(), $dataGrid->getName());

                if (is_array($configuration)) {
                    $dataGridConfiguration = $configuration;
                }
            }
        }

        if (count($dataGridConfiguration)) {
            $this->buildConfiguration($dataGrid, $dataGridConfiguration);
        }
    }

    /**
     * @param string $bundlePath
     * @param string $dataGridName
     * @return bool
     */
    protected function hasDataGridConfiguration($bundlePath, $dataGridName)
    {
        return file_exists(sprintf($bundlePath . '/Resources/config/datagrid/%s.yml', $dataGridName));
    }

    /**
     * @param string $bundlePath
     * @param string $dataGridName
     * @return mixed
     */
    protected function getDataGridConfiguration($bundlePath, $dataGridName)
    {
        $yamlParser = new Parser();
        return $yamlParser->parse(file_get_contents(sprintf($bundlePath . '/Resources/config/datagrid/%s.yml', $dataGridName)));
    }

    /**
     * @param DataGridInterface $dataGrid
     * @param array $configuration
     */
    protected function buildConfiguration(DataGridInterface $dataGrid, array $configuration)
    {
        foreach ($configuration['columns'] as $name => $column) {
            $type = array_key_exists('type', $column)
                ? $column['type']
                : 'text';
            $options = array_key_exists('options', $column)
                ? $column['options']
                : [];

            $dataGrid->addColumn($name, $type, $options);
        }
    }
}
