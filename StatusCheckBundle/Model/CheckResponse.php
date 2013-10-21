<?php

/**
 * Description of CheckResponse
 *
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Model;

class CheckResponse implements \JsonSerializable
{

    const CHECK_UNINITIALIZED = 0;
    const CHECK_PENDING = 'pending';
    const CHECK_STARTED = 'started';
    const CHECK_IN_PROGRESS = 'in_progress';
    const CHECK_SUCCESS = 'success';
    const CHECK_REDIRECTED = 'redirected';
    const CHECK_CONNECTION_FAILED = 'connection_failed';
    const CHECK_REQUEST_SENT = 'request_sent';
    const CHECK_SOCKET_OPEN = 'socket_open';
    const CHECK_DONE = 'done';
    const CHECK_INVALID_HOST = 'invalid_host';
    const CHECK_BROKEN_PIPE = 'broken_pipe';
    const CHECK_STREAM_ERROR = 'stream_error';

    protected static $ID = 1;

    /** @var int Helper attribute, used for building recursive redirects */
    private $parentId;

    /** @var CheckResponse Works only if results are not streaming and checks build a tree */
    protected $redirect = false;

    /** @var Host */
    protected $host = null;

    /** @var string Location host */
    protected $redirectLocation = null;

    /** @var int HTTP status code  */
    protected $statusCode;

    /** @var int HTTP content length */
    protected $contentLength;
    protected $info;

    /** @var int HTTP check status */
    protected $status = self::CHECK_UNINITIALIZED;
    protected $id;

    public function __construct(Host $host)
    {
        $this->host = $host;
        $this->id = self::$ID;
        self::$ID++;
    }

    public function getHost()
    {
        return $this->host();
    }

    public function getRedirect()
    {
        return $this->redirect;
    }

    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getContentLength()
    {
        return $this->contentLength;
    }

    public function setContentLength($contentLength = 0)
    {
        $this->contentLength = $contentLength;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Extracts useful data from an HTTP response
     * @param string HTTP response
     * @return \ivanbatic\StatusCheckBundle\Model\CheckResponse
     * @deprecated This is slow when body content is large
     */
    public function parseResponseBody($responseBody)
    {
        // Extract content length
        $length = [];
        preg_match('/(?<=\bContent-Length:\s)(\d+)/', $responseBody, $length);
        if (isset($length[0])) {
            $this->setContentLength($length[0]);
        } else {
            $pos = stripos($responseBody, '<!doctype');
            $this->setContentLength(strlen($responseBody) - $pos);
        }

        // Extract HTTP status code
        $statusCode = [];
        preg_match('/(?<=\bHTTP\/\d\.\d\s)(\d*)/', $responseBody, $statusCode);
        if (isset($statusCode[0])) {
            $this->setStatusCode($statusCode[0]);
        }

        // Extract redirect location
        if (in_array($this->getStatusCode(), [301, 302])) {
            $redirectLocation = [];
            preg_match('/(?<=\bLocation:\s)(.*)/', $responseBody, $redirectLocation);

            if (isset($redirectLocation[0])) {
                // Nice catch, had to remove control characters from extracted string.
                $this->setRedirectLocation(ereg_replace("[[:cntrl:]]", "", $redirectLocation[0]));
            }
        }

        return $this;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getRedirectLocation()
    {
        // Google+ returns circular redirects...
        if ($this->host->getOriginal() == $this->redirectLocation) {
            $this->setInfo('circular_redirecion');
            return false;
        } else {
            if (substr($this->redirectLocation, 0, 1) !== '/') {
                // If it's an absolute redirect
                return $this->redirectLocation;
            } else {
                // If it's relative
                $p = $this->host->getHost();
                if ($this->host->getPort()) {
                    $p .= ':' . $this->host->getPort();
                }
                $p .= '/' . ltrim($this->redirectLocation, '/');
                return $p;
            }
        }
    }

    public function setRedirectLocation($redirectLocation)
    {
        $this->redirectLocation = $redirectLocation;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function jsonSerialize()
    {
        $data = [
            'status_code'    => $this->getStatusCode(),
            'content_length' => $this->getContentLength(),
            'request_url'    => $this->host->getOriginal(),
            'id'             => $this->getId(),
            'status'         => $this->getStatus(),
            'parent_id'      => $this->getParentId(),
            'host_id'        => $this->host->getId(),
            'host_parent_id' => $this->host->getParentId(),
            'info'           => $this->getInfo()
        ];
        return $data;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function setInfo($info)
    {
        $this->info = $info;
        return $this;
    }

}
