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
        $hosts = ['www.smgbonline.com'];
        $batch = new CheckBatch();
        foreach ($hosts as $host) {
            $batch->addHost(new Host($host));
        }

        $checker = new StatusChecker($batch, true);
        $first = true;
        ob_implicit_flush(true);
        foreach ($checker->check() as $response) {
            echo "\n" . json_encode($response);
            $first = false;
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
        } elseif(count($hosts) > 1000){
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
        $first = true;
        ob_implicit_flush(true);
        foreach ($checker->check() as $response) {
            echo "\n" . json_encode($response);
            $first = false;
        };
        ob_implicit_flush(false);
        exit();
    }

}
