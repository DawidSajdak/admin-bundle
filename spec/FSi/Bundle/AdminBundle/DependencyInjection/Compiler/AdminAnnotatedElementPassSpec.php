<?php

namespace spec\FSi\Bundle\AdminBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use FSi\Bundle\AdminBundle\Annotation\Element;
use FSi\Bundle\AdminBundle\Finder\AdminClassFinder;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AdminAnnotatedElementPassSpec extends ObjectBehavior
{
    function let(AnnotationReader $annotationReader, AdminClassFinder $adminClassFinder)
    {
        $this->beConstructedWith($annotationReader, $adminClassFinder);

    }

    function it_registers_annotated_admin_classes_as_services(
        ContainerBuilder $container,
        AnnotationReader $annotationReader,
        AdminClassFinder $adminClassFinder
    ) {
        $container->getParameter('kernel.bundles')->willReturn(array(
            'FSi\Bundle\AdminBundle\spec\fixtures\MyBundle',
            'FSi\Bundle\AdminBundle\FSiAdminBundle',
            'Symfony\Bundle\FrameworkBundle\FrameworkBundle'
        ));

        $baseDir = __DIR__ . '/../../../../../..';
        $adminClassFinder->findClasses(array(
            realpath($baseDir . '/spec/fixtures/Admin'),
            realpath($baseDir . '/Admin')
        ))->willReturn(array(
            'FSi\Bundle\AdminBundle\spec\fixtures\Admin\SimpleAdminElement',
            'FSi\Bundle\AdminBundle\spec\fixtures\Admin\CRUDElement'
        ));

        $annotationReader->getClassAnnotation(
            Argument::allOf(
                Argument::type('ReflectionClass'),
                Argument::which('getName', 'FSi\Bundle\AdminBundle\spec\fixtures\Admin\CRUDElement')
            ),
            'FSi\\Bundle\\AdminBundle\\Annotation\\Element'
        )->willReturn(null);

        $annotationReader->getClassAnnotation(
            Argument::allOf(
                Argument::type('ReflectionClass'),
                Argument::which('getName', 'FSi\Bundle\AdminBundle\spec\fixtures\Admin\SimpleAdminElement')
            ),
            'FSi\\Bundle\\AdminBundle\\Annotation\\Element'
        )->willReturn(new Element(array()));

        $container->addDefinitions(Argument::that(function ($definitions) {
            if (count($definitions) !== 1) {
                return false;
            }

            /** @var Definition $definition */
            $definition = $definitions[0];
            if ($definition->getClass() !== 'FSi\Bundle\AdminBundle\spec\fixtures\Admin\SimpleAdminElement') {
                return false;
            }

            if (!$definition->hasTag('admin.element')) {
                return false;
            };

            return true;
        }))->shouldBeCalled();

        $this->process($container);
    }
}
