<?php

namespace ivanbatic\StatusCheckBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
class DefaultController extends Controller
{
    public function indexAction()
    {
        return new Response('Hello World!',200);
//        return $this->render('ivanbaticStatusCheckBundle:Default:index.html.twig', array('name' => $name));
    }
}
