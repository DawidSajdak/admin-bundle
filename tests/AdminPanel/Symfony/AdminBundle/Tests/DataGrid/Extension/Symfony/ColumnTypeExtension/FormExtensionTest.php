<?php

namespace AdminPanel\Symfony\AdminBundleBundle\Tests\DataGrid\Extension\Symfony\ColumnTypeExtension;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use AdminPanel\Symfony\AdminBundle\DataGrid\Extension\Symfony\ColumnTypeExtension\FormExtension;
use AdminPanel\Symfony\AdminBundleBundle\Tests\Fixtures\Entity;
use AdminPanel\Symfony\AdminBundleBundle\Tests\Fixtures\EntityCategory;
use FSi\Component\DataGrid\DataGrid;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ValidatorBuilder;

class FormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormExtension
     */
    private $extension;

    /**
     * @var DataGrid
     */
    private $dataGrid;

    protected function setUp()
    {
        $entities = array(
            new EntityCategory(1, 'category name 1'),
            new EntityCategory(2, 'category name 2'),
        );

        $configuration = $this->getMock('Doctrine\ORM\Configuration');

        $objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $objectManager->expects($this->any())
            ->method('getExpressionBuilder')
            ->will($this->returnValue(new Expr()));

        $query = $this->getMock('Doctrine\ORM\AbstractQuery',
            array('execute', '_doExecute', 'getSql', 'setFirstResult', 'setMaxResults'),
            array($objectManager));
        $query->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($entities));

        $query->expects($this->any())
            ->method('setFirstResult')
            ->will($this->returnValue($query));

        $query->expects($this->any())
            ->method('setMaxResults')
            ->will($this->returnValue($query));

        $objectManager->expects($this->any())
            ->method('createQuery')
            ->withAnyParameters()
            ->will($this->returnValue($query));

        $queryBuilder = new QueryBuilder($objectManager);

        $entityClass = EntityCategory::class;
        $classMetadata = new ClassMetadata($entityClass);
        $classMetadata->identifier = array('id');
        $classMetadata->fieldMappings = array(
            'id' => array(
                'fieldName' => 'id',
                'type' => 'integer',
            )
        );
        $classMetadata->reflFields = array(
            'id' => new \ReflectionProperty($entityClass, 'id'),
        );

        $repository = $this->getMock('Doctrine\ORM\EntityRepository', array(), array($objectManager, $classMetadata));
        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->withAnyParameters()
            ->will($this->returnValue($queryBuilder));
        $repository->expects($this->any())
            ->method('findAll')
            ->will($this->returnValue($entities));

        $objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->withAnyParameters()
            ->will($this->returnValue($classMetadata));
        $objectManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));
        $objectManager->expects($this->any())
            ->method('contains')
            ->will($this->returnValue(true));

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($objectManager));
        $managerRegistry->expects($this->any())
            ->method('getManagers')
            ->will($this->returnValue(array()));

        if ($this->isSymfonyForm27()) {
            $tokenManager = new CsrfTokenManager();
        } else {
            $tokenManager = new DefaultCsrfProvider('tests');
        }

        $validatorBuilder = new ValidatorBuilder();
        $resolvedTypeFactory = new ResolvedFormTypeFactory();
        $formRegistry = new FormRegistry(array(
            new CoreExtension(),
            new DoctrineOrmExtension($managerRegistry),
            new CsrfExtension(new CsrfTokenManager()),
            new ValidatorExtension($validatorBuilder->getValidator())
        ),
            $resolvedTypeFactory
        );

        $formFactory = new FormFactory($formRegistry, $resolvedTypeFactory);

        $this->dataGrid = $this->getMock('FSi\Component\DataGrid\DataGridInterface');
        $this->dataGrid->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('grid'));

        $this->extension = new FormExtension($formFactory);
    }

    public function testSimpleBindData()
    {
        $column = $this->createColumnMock();
        $this->setColumnId($column, 'text');
        $this->setColumnOptions($column, array(
            'field_mapping' => array('name', 'author'),
            'editable' => true,
            'form_options' => array(),
            'form_type' => array(
                'name' => array('type' => $this->isSymfonyForm28() ? 'Symfony\Component\Form\Extension\Core\Type\TextType' : 'text'),
                'author' => array('type' => $this->isSymfonyForm28() ? 'Symfony\Component\Form\Extension\Core\Type\TextType' : 'text'),
            )
        ));

        $object = new Entity('old_name');
        $data = array(
            'name' => 'object',
            'author' => 'norbert@fsi.pl',
            'invalid_data' => 'test'
        );

        $this->extension->bindData($column, $data, $object, 1);

        $this->assertSame('norbert@fsi.pl', $object->getAuthor());
        $this->assertSame('object', $object->getName());
    }


    public function testAvoidBindingDataWhenFormIsNotValid()
    {
        $column = $this->createColumnMock();
        $this->setColumnId($column, 'text');
        $this->setColumnOptions($column, array(
            'field_mapping' => array('name', 'author'),
            'editable' => true,
            'form_options' => array(
                'author' => [
                    'constraints' => [
                        new Email()
                    ]
                ]
            ),
            'form_type' => array(
                'name' => array('type' => $this->isSymfonyForm28() ? 'Symfony\Component\Form\Extension\Core\Type\TextType' : 'text'),
                'author' => array('type' => $this->isSymfonyForm28() ? 'Symfony\Component\Form\Extension\Core\Type\TextType' : 'text'),
            )
        ));

        $object = new Entity('old_name');

        $data = array(
            'name' => 'object',
            'author' => 'invalid_value',
        );

        $this->extension->bindData($column, $data, $object, 1);

        $this->assertNull($object->getAuthor());
        $this->assertSame('old_name', $object->getName());
    }

    public function testEntityBindData()
    {
        $nestedEntityClass = EntityCategory::class;

        $column = $this->createColumnMock();
        $this->setColumnId($column, 'entity');
        $this->setColumnOptions($column, array(
            'editable' => true,
            'relation_field' => 'category',
            'field_mapping' => array('name'),
            'form_options' => array(
                'category' => array(
                    'class' => $nestedEntityClass,
                )
            ),
            'form_type' => array(),
        ));

        $object = new Entity('name123');
        $data = array(
            'category' => 1,
        );

        $this->assertSame($object->getCategory(), null);

        $this->extension->bindData($column, $data, $object, 1);

        $this->assertInstanceOf($nestedEntityClass, $object->getCategory());
        $this->assertSame('category name 1', $object->getCategory()->getName());
    }

    private function createColumnMock()
    {
        $column = $this->getMock('FSi\Component\DataGrid\Column\ColumnTypeInterface');

        $column->expects($this->any())
            ->method('getDataMapper')
            ->will($this->getDataMapperReturnCallback());

        $column->expects($this->any())
            ->method('getDataGrid')
            ->will($this->returnValue($this->dataGrid));

        return $column;
    }

    private function setColumnId($column, $id)
    {
        $column->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
    }

    private function setColumnOptions($column, $optionsMap)
    {
        $column->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function($option) use($optionsMap) {
                return $optionsMap[$option];
            }));
    }

    private function getDataMapperReturnCallback()
    {
        $dataMapper = $this->getMock('FSi\Component\DataGrid\DataMapper\DataMapperInterface');
        $dataMapper->expects($this->any())
            ->method('getData')
            ->will($this->returnCallback(function($field, $object){
                $method = 'get' . ucfirst($field);
                return $object->$method();
            }));

        $dataMapper->expects($this->any())
            ->method('setData')
            ->will($this->returnCallback(function($field, $object, $value){
                $method = 'set' . ucfirst($field);
                return $object->$method($value);
            }));

        return $this->returnCallback(function() use ($dataMapper) {
            return $dataMapper;
        });
    }

    /**
     * @return bool
     */
    private function isSymfonyForm27()
    {
        return method_exists('Symfony\Component\Form\FormTypeInterface', 'configureOptions');
    }

    private function isSymfonyForm28()
    {
        return method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix');
    }
}