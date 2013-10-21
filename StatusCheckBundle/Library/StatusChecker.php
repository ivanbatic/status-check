<?php

/**
 * Description of StatusChecker
 *
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Library;

use ivanbatic\StatusCheckBundle\Model\CheckBatch;
use ivanbatic\StatusCheckBundle\Model\CheckResponse;
use ivanbatic\StatusCheckBundle\Model\Host;

class StatusChecker
{

    const SOCKET_OPEN_TIMEOUT = 2; // timeout for opening a socket
    const SOCKET_READ_INTERVAL = 500000; // in microseconds
    const SOCKET_READ_TURN = 500; // in milliseconds
    const BROKEN_PIPE_LIMIT = 1000; // How many broken pipe errors are needed in order to terminate the connection

    protected $hosts;
    protected $sockets = [];

    /** @var CheckResponse */
    protected $responses = [];

    public function __construct(CheckBatch $batch)
    {
        $this->hosts = $batch;
    }

    public static function createHttpRequest($type = 'HEAD', $host, $path = '/', $httpVersion = '1.0')
    {
        $request = "{$type} {$path} HTTP/{$httpVersion}\r\n" .
            "Host: {$host}\r\n" .
            "Connection: close\r\n\r\n";
        return $request;
    }

    public static function utimeInMs()
    {
        return round(microtime(true) * 1000);
    }

    protected function run()
    {
        // Set async flag
        $flags = STREAM_CLIENT_ASYNC_CONNECT;
        // Init referenced vars

        $errno = $errstr = null;

        $pendingHosts = clone $this->hosts;
        $openSockets = [];
        $unsentRequestsToSockets = [];
        $resolvedHosts = [];
        $unreadSockets = [];
        // index => content map of what we got from the get request
        $readContent = [];
        // Number of broken pipe errors by index
        $brokenPipes = [];

        // Create a response for each host and yield uninitialized status
        foreach ($pendingHosts as $index => $host) {
            $brokenPipes[$index] = 0;
            $response = new CheckResponse($host);
            $response->setParentId($host->getParentId());
            $this->responses[$index] = $response->setStatus(CheckResponse::CHECK_PENDING);
            yield($this->responses[$index]);
        }

        // Callback to fail a socket connection
        $failConnection = function($index, $why) use ($pendingHosts, $openSockets, $unsentRequestsToSockets, $resolvedHosts, $unreadSockets) {
                $possibleLists = ['pendingHosts', 'openSockets', 'unsentRequestsToSockets', 'resolvedHosts', 'unreadSockets'];
                foreach ($possibleLists as $var) {
                    $arr = $$var;
                    if (isset($arr[$index])) {
                        unset($arr[$index]);
                    }
                };
                return $this->responses[$index]->setStatus($why);
            };

        // Main loop, go on until all pending hosts are gone
        while (count($pendingHosts)) {
            $socketOpenStart = self::utimeInMs();
            // Open a socket for each host
            foreach ($pendingHosts as $index => $host) {
                // If socket is already open or it's resolved, skip it
                if (isset($openSockets[$index]) || isset($resolvedHosts[$index])) {
                    continue;
                }
                // Open a socket
                try {

                    // Doesn't work on remote host
                    $ip = self::getAddrByHost($host->getHost());

                    // Should work on remote host, but timeout is really bad
                    // sh...geekstorage resolves everything
//                    $ip = filter_var(gethostbyname($host->getHost(), FILTER_VALIDATE_IP));

                    if ($ip === false) {
                        yield $failConnection($index, CheckResponse::CHECK_INVALID_HOST);
                        continue;
                    }
                    $h = $host->getHttpRequestHost($ip);
                    $openSockets[$index] = $unsentRequestsToSockets[$index] = stream_socket_client($h, $errno, $errstr, self::SOCKET_OPEN_TIMEOUT, $flags);
                    stream_set_blocking($openSockets[$index], 0);
                    $this->responses[$index]->setStatus(CheckResponse::CHECK_SOCKET_OPEN);
                } catch (\Exception $e) {
                    yield $failConnection($index, CheckResponse::CHECK_CONNECTION_FAILED);
                    unset($unsentRequestsToSockets[$index]);
                }
                yield($this->responses[$index]);
                // If time exceeded, go and send a request
                $diff = self::utimeInMs() - $socketOpenStart;
                if ($diff > self::SOCKET_READ_TURN) {
                    break;
                }
            }
            // Send requests
            $requests = $read = $except = [];
            $unsent = $unsentRequestsToSockets;
            $sendRequestStart = self::utimeInMs();
            foreach ($unsent as $index => $socket) {

                $write = $unsentRequestsToSockets;
                $request[$index] = self::createHttpRequest('GET', $this->hosts[$index]->getHttpRequestHost(null, false, false), $this->hosts[$index]->getHttpRequestPath());
                // Write the request until all of it is gone
                while ($request[$index]) {
                    // Reload sockets
                    $write = $unsent;
                    if (stream_select($read, $write, $except, 0, self::SOCKET_READ_INTERVAL)) {
                        // Catch the exception if the connection is not open yet
                        try {
                            $wrote = fwrite($socket, $request[$index], strlen($request[$index]));
                            $request[$index] = substr($request[$index], $wrote);
                        } catch (\Exception $e) {
                            $brokenPipes[$index]++;
                            if ($brokenPipes[$index] > self::BROKEN_PIPE_LIMIT) {
                                yield $failConnection($index, CheckResponse::CHECK_BROKEN_PIPE);
                                unset($unsent[$index]);
                                unset($unsentRequestsToSockets[$index]);
                                fclose($socket);
                            }
                            continue 2;
                            // Conection is not open, try again after timeout
                        }
                    } else {
                        // Couldn't connect to the stream for writing
                        yield $failConnection($index, CheckResponse::CHECK_STREAM_ERROR);
                        unset($unsent[$index]);
                        unset($unsentRequestsToSockets[$index]);
                        fclose($socket);
                        continue 2;
                    }
                }
                // Remove from queue

                unset($unsentRequestsToSockets[$index]);
                // Add to reading queue
                $unreadSockets[$index] = $socket;
                $readContent[$index] = '';
                // Throw status
                $this->responses[$index]->setStatus(CheckResponse::CHECK_REQUEST_SENT);
                yield($this->responses[$index]);
                // Go on with another if time exceeded
                if ((self::utimeInMs() - $sendRequestStart) > self::SOCKET_READ_TURN) {
                    break;
                }
            }

            // Read responses
            $unread = $unreadSockets;
            foreach ($unread as $index => $socket) {
                $read = $unread;
                $write = $except = [];
                $this->responses[$index]->setStatus(CheckResponse::CHECK_IN_PROGRESS);
                yield $this->responses[$index];
                $readingStart = self::utimeInMs();
                while (!feof($socket)) {
                    $read = [$unread[$index]];
                    if (stream_select($read, $write, $except, 0, self::SOCKET_READ_INTERVAL)) {
                        $line = fgets($socket);
                        $readContent[$index] .= $line;
                    }
                    if ((self::utimeInMs() - $readingStart) > self::SOCKET_READ_TURN) {
                        continue 2;
                    }
                }
                if (feof($socket)) {
                    $this->responses[$index]->parseResponseBody($readContent[$index]);
                    // Follow redirection
                    if ($this->responses[$index]->getRedirectLocation()) {
                        // Create a new host and add it
                        $newHost = new Host($this->responses[$index]->getRedirectLocation());
                        $i = count($this->hosts);
                        $this->hosts->addHost($newHost);
                        $response = new CheckResponse($newHost);
                        $response->setParentId($this->hosts[$index]->getId());
                        $this->responses[$i] = $response->setStatus(CheckResponse::CHECK_PENDING);
                        $pendingHosts[$i] = $newHost;
                        $brokenPipes[$i] = 0;
                        yield($this->responses[$i]);
                    }
                    // If we're done with this stream, clean up
                    unset($unread[$index]);
                    unset($unreadSockets[$index]);
                    unset($pendingHosts[$index]);
                    $resolvedHosts[$index] = true;
                    $this->responses[$index]->setStatus(CheckResponse::CHECK_SUCCESS);
                    yield $this->responses[$index];
                    fclose($socket);
                }
            }
        }
    }

    /**
     * Close all open sockets
     */
    protected function closeSockets()
    {
        foreach ($this->sockets as $socket) {
            // Attempt to close a socket
            try {
                fclose($socket);
            } catch (\Exception $e) {
                
            }
        }
    }

    public function check()
    {
        foreach ($this->run() as $response) {
            yield $response;
        }
    }

    /**
     * Resolves address from host using a shell command
     * This is used because getaddrbyname doesn't have a timeout option 
     * so it blocks stuff in some cases
     * @param string $host
     * @param int $timeout
     * @return mixed string|false IP address if it's resolved or false otherwise
     */
    public static function getAddrByHost($host, $timeout = 1)
    {
        $matches = [];
        $result = false;
        $query = shell_exec("nslookup -timeout={$timeout} -retry=1 {$host}");

        if (preg_match('/\nAddress: (.*)\n/', $query, $matches)) {

            $result = trim($matches[1]);
        }
        $filtered = filter_var($result, FILTER_VALIDATE_IP);
        return $filtered;
    }

    public function __destruct()
    {
        // Just in case
        $this->closeSockets();
    }

}
