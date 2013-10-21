<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

//$collection->add('ivanbatic_status_check_homepage', new Route('/check-status', array(
//    '_controller' => 'ivanbaticStatusCheckBundle:Default:index',
//)));
//$collection->add('ivanbatic_status_check_get', new Route('/check-status', array(
//    '_controller' => 'ivanbaticStatusCheckBundle:Check:index',
//    ), array(), array(), '', array(), array('GET')
//));
//$collection->add('ivanbatic_status_check_post', new Route('/check-status', array(
//    '_controller' => 'ivanbaticStatusCheckBundle:Check:check',
//    ), array(), array(), '', array(), array('POST'))
//);

$collection->add('ivanbatic_status_check_get', new Route('/check-status', array(
    '_controller' => 'ivanbaticStatusCheckBundle:Check:checkMongoRoute',
    ), array(), array(), '', array(), array('GET')
));

$collection->add('ivanbatic_status_check_post', new Route('/check-status', array(
    '_controller' => 'ivanbaticStatusCheckBundle:Check:passToMongo',
    ), array(), array(), '', array(), array('POST'))
);

$collection->add('ivanbatic_status_check_mongo_get', new Route('/', array(
    '_controller' => 'ivanbaticStatusCheckBundle:App:index'))
);

return $collection;
