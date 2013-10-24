<?php

/**
 * MongoDB check entity
 *
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(collection="check_requests")
 */
class Check implements \JsonSerializable
{

    /**
     * Entry id
     * @MongoDB\Id
     */
    protected $id;

    /**
     * RequestUrl name
     * @MongoDB\String(name="request_url")
     */
    protected $requestUrl;

    /**
     * Processing status
     * @MongoDB\String
     */
    protected $status = 'pending';

    /**
     * Address which request clientated from
     * @MongoDB\String(name="request_client")
     */
    protected $requestClient;

    /**
     * Unix timestamp
     * @MongoDB\Int(name="request_time")
     */
    protected $requestTime;

    /** @MongoDB\Int(name="content_length") */
    protected $contentLength = null;

    /** @MongoDB\String(name="status_code") */
    protected $statusCode = null;

    /** @MongoDB\Int(name="page_index") */
    protected $pageIndex = 0;

    public function __construct()
    {
        $this->setRequestTime(time());
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getRequestTime()
    {
        return $this->requestTime;
    }

    public function setRequestTime($requestTime)
    {
        $this->requestTime = $requestTime;
        return $this;
    }

    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
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

    public function getRequestClient()
    {
        return $this->requestClient;
    }

    public function setRequestClient($requestClient)
    {
        $this->requestClient = $requestClient;
        return $this;
    }

    public function getContentLength()
    {
        return $this->contentLength;
    }

    public function setContentLength($contentLength)
    {
        $this->contentLength = $contentLength;
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

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function getPageIndex()
    {
        return $this->pageIndex;
    }

    public function setPageIndex($pageIndex)
    {
        $this->pageIndex = $pageIndex;
        return $this;
    }

    public function toArray()
    {
        return [
            'id'             => $this->getId(),
            'request_client' => $this->getRequestClient(),
            'request_url'    => $this->getRequestUrl(),
            'status'         => $this->getStatus(),
            'request_time'   => $this->getRequestTime(),
            'status_code'    => $this->getStatusCode(),
            'content_length' => $this->getContentLength(),
            'page_index'     => $this->getPageIndex()
        ];
    }

}
