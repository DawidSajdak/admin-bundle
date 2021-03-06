<?php

declare(strict_types=1);

namespace AdminPanel\Symfony\AdminBundle\Controller;

use AdminPanel\Symfony\AdminBundle\Admin\CRUD\ListElement;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class ListController extends ControllerAbstract
{
    /**
     * @ParamConverter("element", class="\AdminPanel\Symfony\AdminBundle\Admin\CRUD\ListElement")
     * @param \AdminPanel\Symfony\AdminBundle\Admin\CRUD\ListElement $element
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(ListElement $element, Request $request)
    {
        return $this->handleRequest($element, $request, 'fsi_admin_list');
    }
}
