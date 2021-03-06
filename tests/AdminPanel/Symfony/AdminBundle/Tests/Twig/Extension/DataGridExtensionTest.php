<?php

declare(strict_types=1);

namespace AdminPanel\Symfony\AdminBundle\Tests\Twig\Extension;

use AdminPanel\Symfony\AdminBundle\Tests\Fixtures\TwigRuntimeLoader;
use AdminPanel\Symfony\AdminBundle\Twig\Extension\DataGridExtension;
use AdminPanel\Component\DataGrid\Column\CellViewInterface;
use AdminPanel\Component\DataGrid\Column\HeaderViewInterface;
use AdminPanel\Component\DataGrid\DataGridViewInterface;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Tests\Extension\Fixtures\StubTranslator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Norbert Orzechowicz <norbert@fsi.pl>
 */
class DataGridExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var DataGridExtension
     */
    protected $extension;


    public function setUp()
    {
        $subPath = version_compare(Kernel::VERSION, '2.7.0', '<') ? 'Symfony/Bridge/Twig/' : '';
        $loader = new \Twig_Loader_Filesystem([
            __DIR__ . '/../../../../../../../vendor/symfony/twig-bridge/' . $subPath . 'Resources/views/Form',
            __DIR__ . '/../../../../../../../src/AdminPanel/Symfony/AdminBundle/Resources/views', // datagrid base theme
            __DIR__ . '/../../Resources/views', // templates used in tests
        ]);

        $rendererEngine = new TwigRendererEngine([
            'form_div_layout.html.twig',
        ]);
        $renderer = new TwigRenderer($rendererEngine);

        $twig = new \Twig_Environment($loader);
        $twig->addExtension(new TranslationExtension(new StubTranslator()));
        $twig->addExtension(new FormExtension($renderer));
        $twig->addGlobal('global_var', 'global_value');
        $this->twig = $twig;

        if (interface_exists('Twig_RuntimeLoaderInterface')) {
            $twig->addRuntimeLoader(new TwigRuntimeLoader([
                $renderer,
            ]));
        }

        $this->extension = new DataGridExtension(['datagrid.html.twig']);
    }

    /**
     * @expectedException \Twig_Error_Loader
     * @expectedExceptionMessage Unable to find template "this_is_not_valid_path.html.twig"
     */
    public function testInitRuntimeShouldThrowExceptionBecauseNotExistingTheme()
    {
        $this->twig->addExtension(new DataGridExtension(['this_is_not_valid_path.html.twig']));
        $this->twig->initRuntime();
    }

    public function testInitRuntimeWithValidPathToTheme()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
    }

    public function testRenderDataGridWidget()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();

        $datagridView = $this->getDataGridView('grid');
        $datagridView->expects($this->any())
            ->method('getColumns')
            ->will($this->returnValue(
                [
                    'title' => $this->getColumnHeaderView($datagridView, 'text', 'title', 'Title')
                ]
            ));

        $datagridWithThemeView = $this->getDataGridView('grid_with_theme');
        $datagridWithThemeView->expects($this->any())
            ->method('getColumns')
            ->will($this->returnValue(
                [
                    'title' => $this->getColumnHeaderView($datagridWithThemeView, 'text', 'title', 'Title')
                ]
            ));

        $html = $this->twig->render('datagrid/datagrid_widget_test.html.twig', [
            'datagrid' => $datagridView,
            'datagrid_with_theme' => $datagridWithThemeView,
        ]);

        $this->assertSame(
            $html,
            $this->getExpectedHtml('datagrid/datagrid_widget_result.html')
        );
    }

    public function testRenderColumnHeaderWidget()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();

        $datagridView = $this->getDataGridView('grid');
        $datagridWithThemeView = $this->getDataGridView('grid_with_header_theme');

        $headerView = $this->getColumnHeaderView($datagridView, 'text', 'title', 'title');
        $headerWithThemeView = $this->getColumnHeaderView($datagridWithThemeView, 'text', 'title', 'title');

        $html = $this->twig->render('datagrid/header_widget_test.html.twig', [
            'grid_with_header_theme' => $datagridWithThemeView,
            'header' => $headerView,
            'header_with_theme' => $headerWithThemeView,
        ]);

        $this->assertSame(
            $html,
            $this->getExpectedHtml('datagrid/datagrid_header_widget_result.html')
        );
    }

    public function testRenderCellWidget()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();

        $datagridView = $this->getDataGridView('grid');
        $datagridWithThemeView = $this->getDataGridView('grid_with_header_theme');

        $cellView = $this->getColumnCellView($datagridView, 'text', 'title', 'This is value 1');
        $cellWithThemeView = $this->getColumnCellView($datagridWithThemeView, 'text', 'title', 'This is value 2');

        $html = $this->twig->render('datagrid/cell_widget_test.html.twig', [
            'grid_with_header_theme' => $datagridWithThemeView,
            'cell' => $cellView,
            'cell_with_theme' => $cellWithThemeView,
        ]);

        $this->assertSame(
            $html,
            $this->getExpectedHtml('datagrid/datagrid_cell_widget_result.html')
        );
    }

    public function testRenderCellActionWidget()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();

        $datagridView = $this->getDataGridView('grid');
        $datagridWithThemeView = $this->getDataGridView('grid_with_header_theme');

        $cellView = $this->getColumnCellView($datagridView, 'actions', 'action', []);
        $cellWithThemeView = $this->getColumnCellView($datagridWithThemeView, 'actions', 'action', []);

        $html = $this->twig->render('datagrid/action_cell_action_widget_test.html.twig', [
            'grid_with_header_theme' => $datagridWithThemeView,
            'cell' => $cellView,
            'cell_with_theme' => $cellWithThemeView,
        ]);

        $this->assertSame(
            $html,
            $this->getExpectedHtml('datagrid/action_cell_action_widget_result.html')
        );
    }

    public function testDataGridRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
        $template = $this->createMock('\Twig_Template');

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid')
            ->will($this->returnValue(true));

        $this->extension->setBaseTheme($template);
        $datagridView = $this->getDataGridView('grid');

        $template->expects($this->at(3))
            ->method('displayBlock')
            ->with('datagrid', [
                'datagrid' => $datagridView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagrid($datagridView);
    }

    public function testDataGridMultipleTemplates()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();

        $template1 = $this->createMock('\Twig_Template');
        $template1->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid')
            ->will($this->returnValue(false));

        $template1->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template1->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid')
            ->will($this->returnValue(true));

        $template2 = $this->createMock('\Twig_Template');
        $template2->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid')
            ->will($this->returnValue(false));

        $template2->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template2->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid')
            ->will($this->returnValue(false));

        $template2->expects($this->at(3))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $this->extension->setBaseTheme([$template1, $template2]);
        $datagridView = $this->getDataGridView('grid');

        $template1->expects($this->at(3))
            ->method('displayBlock')
            ->with('datagrid', [
                'datagrid' => $datagridView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagrid($datagridView);
    }

    public function testDataGridRenderBlockFromParent()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();

        $template = $this->createMock('\Twig_Template');
        $parent = $this->createMock('\Twig_Template');

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid')
            ->will($this->returnValue(false));

        $template->expects($this->at(3))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue($parent));

        $parent->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid')
            ->will($this->returnValue(true));

        $this->extension->setBaseTheme($template);
        $datagridView = $this->getDataGridView('grid');

        $template->expects($this->at(4))
            ->method('displayBlock')
            ->with('datagrid', [
                'datagrid' => $datagridView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagrid($datagridView);
    }

    public function testDataGridHeaderRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
        $template = $this->createMock('\Twig_Template');

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid_header')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid_header')
            ->will($this->returnValue(true));

        $this->extension->setBaseTheme($template);
        $datagridView = $this->getDataGridView('grid');

        $datagridView->expects($this->any())
            ->method('getColumns')
            ->will($this->returnValue([]));

        $template->expects($this->at(3))
            ->method('displayBlock')
            ->with('datagrid_header', [
                'headers' => [],
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagridHeader($datagridView);
    }

    public function testDataGridColumnHeaderRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
        $template = $this->createMock('\Twig_Template');

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid_column_name_title_header')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid_grid_column_type_text_header')
            ->will($this->returnValue(false));

        $template->expects($this->at(3))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(4))
            ->method('hasBlock')
            ->with('datagrid_column_name_title_header')
            ->will($this->returnValue(false));

        $template->expects($this->at(5))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(6))
            ->method('hasBlock')
            ->with('datagrid_column_type_text_header')
            ->will($this->returnValue(false));

        $template->expects($this->at(7))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(8))
            ->method('hasBlock')
            ->with('datagrid_grid_column_header')
            ->will($this->returnValue(false));

        $template->expects($this->at(9))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(10))
            ->method('hasBlock')
            ->with('datagrid_column_header')
            ->will($this->returnValue(true));

        $this->extension->setBaseTheme($template);
        $datagridView = $this->getDataGridView('grid');
        $headerView = $this->getColumnHeaderView($datagridView, 'text', 'title', 'Title');

        $headerView->expects($this->any())
            ->method('getAttribute')
            ->with('translation_domain')
            ->will($this->returnValue(null));

        $template->expects($this->at(11))
            ->method('displayBlock')
            ->with('datagrid_column_header', [
                'header' => $headerView,
                'translation_domain' => null,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagridColumnHeader($headerView);
    }

    public function testDataGridRowsetRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
        $template = $this->createMock('\Twig_Template');

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid_rowset')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid_rowset')
            ->will($this->returnValue(true));

        $this->extension->setBaseTheme($template);
        $datagridView = $this->getDataGridView('grid');

        $template->expects($this->at(3))
            ->method('displayBlock')
            ->with('datagrid_rowset', [
                'datagrid' => $datagridView,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagridRowset($datagridView);
    }

    public function testDataGridColumnCellRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
        $template = $this->createMock('\Twig_Template');

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid_column_name_title_cell')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid_grid_column_type_text_cell')
            ->will($this->returnValue(false));

        $template->expects($this->at(3))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(4))
            ->method('hasBlock')
            ->with('datagrid_column_name_title_cell')
            ->will($this->returnValue(false));

        $template->expects($this->at(5))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(6))
            ->method('hasBlock')
            ->with('datagrid_column_type_text_cell')
            ->will($this->returnValue(false));

        $template->expects($this->at(7))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(8))
            ->method('hasBlock')
            ->with('datagrid_grid_column_cell')
            ->will($this->returnValue(false));

        $template->expects($this->at(9))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(10))
            ->method('hasBlock')
            ->with('datagrid_column_cell')
            ->will($this->returnValue(true));

        $this->extension->setBaseTheme($template);
        $datagridView = $this->getDataGridView('grid');
        $cellView = $this->getColumnCellView($datagridView, 'text', 'title', 'Value 1');

        $cellView->expects($this->any())
            ->method('getAttribute')
            ->will($this->returnCallback(function ($key) {
                switch ($key) {
                    case 'row':
                        return 0;
                        break;
                }

                return null;
            }));

        $template->expects($this->at(11))
            ->method('displayBlock')
            ->with('datagrid_column_cell', [
                'cell' => $cellView,
                'row_index' => 0,
                'datagrid_name' => 'grid',
                'translation_domain' => null,
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagridColumnCell($cellView);
    }

    public function testDataGridColumnCellFormRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
        $template = $this->createMock('\Twig_Template');

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid_column_name_title_cell_form')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid_grid_column_type_text_cell_form')
            ->will($this->returnValue(false));

        $template->expects($this->at(3))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(4))
            ->method('hasBlock')
            ->with('datagrid_column_name_title_cell_form')
            ->will($this->returnValue(false));

        $template->expects($this->at(5))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(6))
            ->method('hasBlock')
            ->with('datagrid_column_type_text_cell_form')
            ->will($this->returnValue(false));

        $template->expects($this->at(7))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(8))
            ->method('hasBlock')
            ->with('datagrid_grid_column_cell_form')
            ->will($this->returnValue(false));

        $template->expects($this->at(9))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(10))
            ->method('hasBlock')
            ->with('datagrid_column_cell_form')
            ->will($this->returnValue(true));

        $this->extension->setBaseTheme($template);
        $datagridView = $this->getDataGridView('grid');
        $cellView = $this->getColumnCellView($datagridView, 'text', 'title', 'Value 1');

        $cellView->expects($this->any())
            ->method('hasAttribute')
            ->with('form')
            ->will($this->returnValue(true));

        $cellView->expects($this->any())
            ->method('getAttribute')
            ->with('form')
            ->will($this->returnValue('form'));

        $template->expects($this->at(11))
            ->method('displayBlock')
            ->with('datagrid_column_cell_form', [
                'form' => 'form',
                'vars' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagridColumnCellForm($cellView);
    }


    public function testDataGridColumnActionCellActionRenderBlock()
    {
        $this->twig->addExtension($this->extension);
        $this->twig->initRuntime();
        $template = $this->createMock('\Twig_Template');

        $template->expects($this->at(0))
            ->method('hasBlock')
            ->with('datagrid_grid_column_type_action_cell_action_edit')
            ->will($this->returnValue(false));

        $template->expects($this->at(1))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(2))
            ->method('hasBlock')
            ->with('datagrid_column_type_action_cell_action_edit')
            ->will($this->returnValue(false));

        $template->expects($this->at(3))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(4))
            ->method('hasBlock')
            ->with('datagrid_grid_column_type_action_cell_action')
            ->will($this->returnValue(false));

        $template->expects($this->at(5))
            ->method('getParent')
            ->with([])
            ->will($this->returnValue(false));

        $template->expects($this->at(6))
            ->method('hasBlock')
            ->with('datagrid_column_type_action_cell_action')
            ->will($this->returnValue(true));

        $this->extension->setBaseTheme($template);
        $datagridView = $this->getDataGridView('grid');
        $cellView = $this->getColumnCellView($datagridView, 'action', 'actions', []);

        $cellView->expects($this->any())
            ->method('getAttribute')
            ->with('translation_domain')
            ->will($this->returnValue(null));

        $template->expects($this->at(7))
            ->method('displayBlock')
            ->with('datagrid_column_type_action_cell_action', [
                'cell' => $cellView,
                'action' => 'edit',
                'content' => 'content',
                'attr' => [],
                'translation_domain' => null,
                'field_mapping_values' => [],
                'global_var' => 'global_value'
            ])
            ->will($this->returnValue(true));

        $this->extension->datagridColumnActionCellActionWidget($cellView, 'edit', 'content');
    }

    private function getDataGridView($name)
    {
        $datagridView = $this->getMockBuilder('AdminPanel\Component\DataGrid\DataGridViewInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $datagridView->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $datagridView;
    }

    private function getColumnHeaderView(DataGridViewInterface $datagridView, $type, $name, $label = null)
    {
        $column = $this->createMock(HeaderViewInterface::class);

        $column->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($type));

        $column->expects($this->any())
            ->method('getLabel')
            ->will($this->returnValue($label));

        $column->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));


        $column->expects($this->any())
            ->method('getDataGridView')
            ->will($this->returnValue($datagridView));

        return $column;
    }

    private function getColumnCellView(DataGridViewInterface $datagridView, $type, $name, $value)
    {
        $column = $this->createMock(CellViewInterface::class);

        $column->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($type));

        $column->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($value));

        $column->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $column->expects($this->any())
            ->method('getDataGridView')
            ->will($this->returnValue($datagridView));

        return $column;
    }

    private function getExpectedHtml($filename)
    {
        $path = __DIR__ . '/../../Resources/views/expected/' . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf('Invalid expected html file path "%s"', $path));
        }

        return file_get_contents($path);
    }
}
