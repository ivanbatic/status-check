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

class CheckController extends Controller {

    // Get request, made for testing
    public function indexAction() {

        $hosts = [];
        $batch = new CheckBatch();
        foreach ($hosts as $host) {
            $batch->addHost(new Host($host));
        }

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

    public function checkAction() {
        set_time_limit(0);
        $request = Request::createFromGlobals();
        $hosts = $request->request->get('hosts', []);
        if (empty($hosts)) {
            exit();
        } elseif (count($hosts) > 1000) {
            return json_encode('too_many_hosts');
        }

        $batch = new CheckBatch();
        foreach ($hosts as $host) {
            try {
                $h = new Host($host);
                $batch->addHost($h);
            } catch (\Exception $e) {
                // probably caught a malformed url
            }
        }

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

}
