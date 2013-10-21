<?php

/**
 * Description of CheckBatch
 *
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Model;

class CheckBatch implements \Iterator, \ArrayAccess, \Countable
{

    protected $hosts = [];

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

}
