<?php

/**
 * Description of AppController
 *
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class AppController extends Controller
{

    public function indexAction()
    {
        return $this->render('ivanbaticStatusCheckBundle:App:index.html.twig');
    }

}
