<?php

declare(strict_types=1);

namespace AdminPanel\Symfony\AdminBundle\Twig\Extension;

use AdminPanel\Component\DataGrid\DataGridViewInterface;
use AdminPanel\Component\DataGrid\Column\HeaderViewInterface;
use AdminPanel\Component\DataGrid\Column\CellViewInterface;
use AdminPanel\Symfony\AdminBundle\Twig\TokenParser\DataGridThemeTokenParser;

class DataGridExtension extends \Twig_Extension
{
    /**
     * Default theme key in themes array.
     */
    const DEFAULT_THEME = 'default_theme';

    /**
     * @var array
     */
    private $themes;

    /**
     * @var array
     */
    private $themesVars;

    /**
     * @var \Twig_Template[]
     */
    private $baseThemes;

    /**
     * @var \Twig_Environment
     */
    private $environment;

    /**
     * @param string $themes
     */
    public function __construct(array $themes)
    {
        $this->themes = [];
        $this->themesVars = [];
        $this->baseThemes = $themes;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'datagrid';
    }

    /**
     * {@inheritDoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
        for ($i = count($this->baseThemes) - 1; $i >= 0; $i--) {
            $this->baseThemes[$i] = $this->environment->loadTemplate($this->baseThemes[$i]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            'datagrid_widget' => new \Twig_Function_Method($this, 'datagrid', ['is_safe' => ['html']]),
            'datagrid_header_widget' =>  new \Twig_Function_Method($this, 'datagridHeader', ['is_safe' => ['html']]),
            'datagrid_rowset_widget' =>  new \Twig_Function_Method($this, 'datagridRowset', ['is_safe' => ['html']]),
            'datagrid_column_header_widget' =>  new \Twig_Function_Method($this, 'datagridColumnHeader', ['is_safe' => ['html']]),
            'datagrid_column_cell_widget' =>  new \Twig_Function_Method($this, 'datagridColumnCell', ['is_safe' => ['html']]),
            'datagrid_column_cell_form_widget' =>  new \Twig_Function_Method($this, 'datagridColumnCellForm', ['is_safe' => ['html']]),
            'datagrid_column_type_action_cell_action_widget' =>  new \Twig_Function_Method($this, 'datagridColumnActionCellActionWidget', ['is_safe' => ['html']]),
            'datagrid_attributes_widget' =>  new \Twig_Function_Method($this, 'datagridAttributes', ['is_safe' => ['html']])
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenParsers()
    {
        return [
            new DataGridThemeTokenParser(),
        ];
    }

    /**
     * Set theme for specific DataGrid.
     * Theme is nothing more than twig template that contains block required to render
     * DataGrid.
     *
     * @param DataGridViewInterface $dataGrid
     * @param $theme
     * @param array $vars
     */
    public function setTheme(DataGridViewInterface $dataGrid, $theme, array $vars = [])
    {
        $this->themes[$dataGrid->getName()] = ($theme instanceof \Twig_Template)
            ? $theme
            : $this->environment->loadTemplate($theme);

        $this->themesVars[$dataGrid->getName()] = $vars;
    }

    /**
     * Set base theme or themes.
     *
     * @param $theme
     */
    public function setBaseTheme($theme)
    {
        $themes = is_array($theme) ? $theme : [$theme];

        $this->baseThemes = [];
        foreach ($themes as $theme) {
            $this->baseThemes[] = ($theme instanceof \Twig_Template)
                ? $theme
                : $this->environment->loadTemplate($theme);
        }
    }

    /**
     * @param \AdminPanel\Component\DataGrid\DataGridViewInterface $view
     * @return string
     */
    public function datagrid(DataGridViewInterface $view)
    {
        $blockNames = [
            'datagrid_' . $view->getName(),
            'datagrid',
        ];

        $context = [
            'datagrid' => $view,
            'vars' => $this->getVars($view)
        ];

        return $this->renderTheme($view, $context, $blockNames);
    }

    /**
     * Render header row in datagrid.
     *
     * @param \AdminPanel\Component\DataGrid\DataGridViewInterface $view
     * @param array $vars
     * @return string
     */
    public function datagridHeader(DataGridViewInterface $view, array $vars = [])
    {
        $blockNames = [
            'datagrid_' . $view->getName() . '_header',
            'datagrid_header',
        ];

        $context = [
            'headers' => $view->getColumns(),
            'vars' => array_merge(
                $this->getVars($view),
                $vars
            )
        ];

        return $this->renderTheme($view, $context, $blockNames);
    }

    /**
     * Render column header.
     *
     * @param HeaderViewInterface $view
     * @param array $vars
     * @return string
     */
    public function datagridColumnHeader(HeaderViewInterface $view, array $vars = [])
    {
        $dataGridView = $view->getDataGridView();
        $blockNames = [
            'datagrid_' . $dataGridView->getName() . '_column_name_' . $view->getName() . '_header',
            'datagrid_' . $dataGridView->getName() . '_column_type_' . $view->getType() . '_header',
            'datagrid_column_name_' . $view->getName() . '_header',
            'datagrid_column_type_' . $view->getType() . '_header',
            'datagrid_' . $dataGridView->getName() . '_column_header',
            'datagrid_column_header',
        ];

        $context = [
            'header' => $view,
            'translation_domain' => $view->getAttribute('translation_domain'),
            'vars' => array_merge(
                $this->getVars($view->getDataGridView()),
                $vars
            )
        ];

        return $this->renderTheme($dataGridView, $context, $blockNames);
    }

    /**
     * Render DataGrid rows except header.
     *
     * @param DataGridViewInterface $view
     * @param array $vars
     * @return string
     */
    public function datagridRowset(DataGridViewInterface $view, array $vars = [])
    {
        $blockNames = [
            'datagrid_' . $view->getName() . '_rowset',
            'datagrid_rowset',
        ];

        $context = [
            'datagrid' => $view,
            'vars' => array_merge(
                $this->getVars($view),
                $vars
            )
        ];

        return $this->renderTheme($view, $context, $blockNames);
    }

    /**
     * Render column cell.
     *
     * @param \AdminPanel\Component\DataGrid\Column\CellViewInterface $view
     * @param array $vars
     * @return string
     */
    public function datagridColumnCell(CellViewInterface $view, array $vars = [])
    {
        $dataGridView = $view->getDataGridView();
        $blockNames = [
            'datagrid_' . $dataGridView->getName() . '_column_name_' . $view->getName() . '_cell',
            'datagrid_' . $dataGridView->getName() . '_column_type_' . $view->getType() . '_cell',
            'datagrid_column_name_' . $view->getName() . '_cell',
            'datagrid_column_type_' . $view->getType() . '_cell',
            'datagrid_' . $dataGridView->getName() . '_column_cell',
            'datagrid_column_cell',
        ];

        $context = [
            'cell' => $view,
            'row_index' => $view->getAttribute('row'),
            'datagrid_name' => $dataGridView->getName(),
            'translation_domain' => $view->getAttribute('translation_domain'),
            'vars' => array_merge(
                $this->getVars($dataGridView),
                $vars
            )
        ];

        return $this->renderTheme($dataGridView, $context, $blockNames);
    }

    /**
     * Render column form if exists.
     *
     * @param \AdminPanel\Component\DataGrid\Column\CellViewInterface $view
     * @param array $vars
     * @return string
     */
    public function datagridColumnCellForm(CellViewInterface $view, array $vars = [])
    {
        if (!$view->hasAttribute('form')) {
            return ;
        }

        $dataGridView = $view->getDataGridView();
        $blockNames = [
            'datagrid_' . $dataGridView->getName() . '_column_name_' . $view->getName() . '_cell_form',
            'datagrid_' . $dataGridView->getName() . '_column_type_' . $view->getType() . '_cell_form',
            'datagrid_column_name_' . $view->getName() . '_cell_form',
            'datagrid_column_type_' . $view->getType() . '_cell_form',
            'datagrid_' . $dataGridView->getName() . '_column_cell_form',
            'datagrid_column_cell_form',
        ];

        $context = [
            'form' => $view->getAttribute('form'),
            'vars' => array_merge(
                $this->getVars($view->getDataGridView()),
                $vars
            )
        ];

        return $this->renderTheme($dataGridView, $context, $blockNames);
    }

    /**
     * @param \AdminPanel\Component\DataGrid\Column\CellViewInterface $view
     * @param $action
     * @param $content
     * @param array $urlAttrs
     * @param array $fieldMappingValues
     * @return string
     */
    public function datagridColumnActionCellActionWidget(CellViewInterface $view, $action, $content, $urlAttrs = [], $fieldMappingValues = [])
    {
        $dataGridView = $view->getDataGridView();
        $blockNames = [
            'datagrid_' . $dataGridView->getName() . '_column_type_action_cell_action_' . $action,
            'datagrid_column_type_action_cell_action_' . $action ,
            'datagrid_' . $dataGridView->getName() . '_column_type_action_cell_action',
            'datagrid_column_type_action_cell_action',
        ];

        $context = [
            'cell' => $view,
            'action' => $action,
            'content' => $content,
            'attr' => $urlAttrs,
            'translation_domain' => $view->getAttribute('translation_domain'),
            'field_mapping_values' => $fieldMappingValues
        ];

        return $this->renderTheme($dataGridView, $context, $blockNames);
    }

    /**
     * Render html element attributes.
     * This function is only for internal use.
     *
     * @param array $attributes
     * @param null $translationDomain
     * @return string
     */
    public function datagridAttributes(array $attributes, $translationDomain = null)
    {
        $attrs = [];

        foreach ($attributes as $attributeName => $attributeValue) {
            if ($attributeName == 'title') {
                $attrs[] = $attributeName . '="' . $this->environment->getExtension('translator')->trans($attributeValue, [], $translationDomain) . '"';
                continue;
            }

            $attrs[] = $attributeName . '="' . $attributeValue . '"';
        }

        return ' ' . implode(' ', $attrs);
    }

    /**
     * Return list of templates that might be useful to render DataGridView.
     * Always the last template will be default one.
     *
     * @param \AdminPanel\Component\DataGrid\DataGridViewInterface $dataGrid
     * @return array
     */
    private function getTemplates(DataGridViewInterface $dataGrid)
    {
        $templates = [];

        if (isset($this->themes[$dataGrid->getName()])) {
            $templates[] = $this->themes[$dataGrid->getName()];
        }

        for ($i = count($this->baseThemes) - 1; $i >= 0; $i--) {
            $templates[] = $this->baseThemes[$i];
        }

        return $templates;
    }

    /**
     * Return vars passed to theme. Those vars will be added to block context.
     *
     * @param \AdminPanel\Component\DataGrid\DataGridViewInterface $dataGrid
     * @return array
     */
    private function getVars(DataGridViewInterface $dataGrid)
    {
        if (isset($this->themesVars[$dataGrid->getName()])) {
            return $this->themesVars[$dataGrid->getName()];
        }

        return [];
    }

    /**
     * @param \AdminPanel\Component\DataGrid\DataGridViewInterface $datagridView
     * @param array $contextVars
     * @param $availableBlocks
     * @return string
     */
    private function renderTheme(DataGridViewInterface $datagridView, array $contextVars = [], $availableBlocks = [])
    {
        $templates = $this->getTemplates($datagridView);

        $contextVars = $this->environment->mergeGlobals($contextVars);

        ob_start();

        foreach ($availableBlocks as $blockName) {
            foreach ($templates as $template) {
                if (false !== ($template = $this->findTemplateWithBlock($template, $blockName))) {
                    $template->displayBlock($blockName, $contextVars);

                    return ob_get_clean();
                }
            }
        }

        return ob_get_clean();
    }

    /**
     * @param \Twig_Template $template
     * @param string $blockName
     * @return \Twig_Template|bool
     */
    private function findTemplateWithBlock(\Twig_Template $template, $blockName)
    {
        if ($template->hasBlock($blockName)) {
            return $template;
        }

        // Check parents
        if (false !== ($parent = $template->getParent([]))) {
            if ($this->findTemplateWithBlock($parent, $blockName) !== false) {
                return $template;
            }
        }

        return false;
    }
}
