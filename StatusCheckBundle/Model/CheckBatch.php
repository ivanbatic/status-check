<?php

/**
 * Description of CheckBatch
 *
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Model;

class CheckBatch implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{

    protected $hosts = [];
    protected $requestTime;
    protected $requestClient;
    protected $pageIndex = 0;

    public function __construct()
    {
        $this->setRequestTime(time());
    }

    public function addHost(Host $host)
    {
        if (!in_array($host, $this->hosts)) {
            $this->hosts[] = $host;
        }
        return $this;
    }

    public function current()
    {
        return current($this->hosts);
    }

    public function key()
    {
        return key($this->hosts);
    }

    public function next()
    {
        return next($this->hosts);
    }

    public function rewind()
    {
        reset($this->hosts);
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

    public function valid()
    {
        $key = key($this->hosts);
        return isset($this->hosts[$key]);
    }

    public function offsetExists($offset)
    {
        return isset($this->hosts[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->hosts[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($value instanceof Host) {
            $this->hosts[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->hosts[$offset]);
    }

    public function count()
    {
        return count($this->hosts);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function getHosts()
    {
        return $this->hosts;
    }

    public function setHosts($hosts)
    {
        $this->hosts = $hosts;
        return $this;
    }

    public function getRequestTime()
    {
        return $this->requestTime;
    }

    protected function setRequestTime($requestTime)
    {
        $this->requestTime = $requestTime;
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

    public function toArray()
    {
        $hosts = [];
        foreach ($this->hosts as $host) {
            $hosts[] = $host->toArray();
        }
        return [
            'hosts'          => $hosts,
            'request_time'   => $this->getRequestTime(),
            'request_client' => $this->getRequestClient(),
            'page_index'     => $this->getPageIndex()
        ];
    }

}
