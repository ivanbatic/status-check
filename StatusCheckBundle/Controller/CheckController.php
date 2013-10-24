<?php

/**
 * Description of CheckController
 *
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use ivanbatic\StatusCheckBundle\Model\Host;
use ivanbatic\StatusCheckBundle\Model\CheckBatch;
use ivanbatic\StatusCheckBundle\Library\StatusChecker;
use Symfony\Component\HttpFoundation\Request;
use ivanbatic\StatusCheckBundle\Library\MongoRouter;
use Symfony\Component\HttpFoundation\JsonResponse;

class CheckController extends Controller
{

    // Get request, made for testing
    public function indexAction()
    {

        $hosts = [];
        $batch = $this->createBatch($hosts);
        $checker = new StatusChecker($batch, true);

        // works for local apache
        ob_implicit_flush(true);
        foreach ($checker->check() as $response) {
            // Has to be padded on remote server, it won't flush it otherwise
            echo str_pad("\n" . json_encode($response), 1024);
            // works for litespeed on GS
            @ob_flush();
            flush();
            // -----------------------
        };
        ob_implicit_flush(false);
        exit();
    }

    public function checkAction()
    {
        set_time_limit(0);
        $request = Request::createFromGlobals();
        $hosts = $request->request->get('hosts', []);
        if (empty($hosts)) {
            exit();
        } elseif (count($hosts) > 1000) {
            return json_encode('too_many_hosts');
        }

        $batch = $this->createBatch($hosts);
        $checker = new StatusChecker($batch);
        ob_implicit_flush(true);
        foreach ($checker->check() as $response) {
            echo str_pad("\n" . json_encode($response), 1024);
            // works for litespeed on GS
            @ob_flush();
            flush();
            // -----------------------
        };
        ob_implicit_flush(false);
        exit();
    }

    /**
     * Post request to /status-check goes here now
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function passToMongoAction()
    {
        $request = Request::createFromGlobals();
        $hosts = $request->request->get('hosts', []);
        if (empty($hosts)) {
            exit();
        } elseif (count($hosts) > 1000) {
            return json_encode('too_many_hosts');
        }
        $batch = $this->createBatch($hosts);
        $mongoRouter = new MongoRouter($this->get('doctrine_mongodb')->getManager());
        $inserted = $mongoRouter->insertBatch($batch->setPageIndex($request->request->get('page_index', 0)));
        return new JsonResponse($inserted);
    }

    /**
     * Creates a new batch out of given hosts
     * @param array $hosts
     * @return \ivanbatic\StatusCheckBundle\Model\CheckBatch
     */
    protected function createBatch(array $hosts)
    {
        $batch = new CheckBatch();
        $batch->setRequestClient($_SERVER['REMOTE_ADDR']);
        foreach ($hosts as $host) {
            try {
                $h = new Host($host);
                $batch->addHost($h);
            } catch (\Exception $e) {
                // probably caught a malformed url
            }
        }
        return $batch;
    }

    public function deletePageFromMongoAction()
    {
        $mongoRouter = new MongoRouter($this->get('doctrine_mongodb')->getManager());
        $request = Request::createFromGlobals();
        $pageIndex = $request->request->get('page_index');

        $mongoRouter = new MongoRouter($this->get('doctrine_mongodb')->getManager());
        $result = $mongoRouter->deleteClientPage($pageIndex, $_SERVER['REMOTE_ADDR']);

        return new JsonResponse([
            'client'     => $_SERVER['REMOTE_ADDR'],
            'page_index' => $pageIndex,
            'success'    => $result
        ]);
    }

    public function deleteFirstPageFromMongoAction()
    {
//        $request = Request::createFromGlobals();
//        $pageIndex = $request->request->get('page_index');
//
//        $mongoRouter = new MongoRouter($this->get('doctrine_mongodb')->getManager());
//        $result = $mongoRouter->deleteClientPage(1, '127.0.0.1');
//        return new JsonResponse(['success' => $result]);
    }

}
