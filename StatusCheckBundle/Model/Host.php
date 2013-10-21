<?php

/**
 * Description of HostModel
 *
 * @author Ivan Batić <ivan.batic@live.com>
 */

namespace ivanbatic\StatusCheckBundle\Model;

class Host
{

    const DEFAULT_SCHEME = 'http';

    protected static $ID = 1;
    // Helper attribute, used for building recursive redirects
    private $parentId;
    protected $id;
    protected $host;
    protected $port;
    protected $scheme;
    protected $original;
    protected $ip;
    protected $query = '';
    protected $path = '/';

    public function __construct($url)
    {

        // If not a string, it's not good
        if (is_string($url)) {

            $this->setOriginal($url);
            // Check if the url is valid
            $parsed = parse_url($this->getOriginal());
            if (is_array($parsed)) {
                if (!isset($parsed['host'])) {
                    $parsed = parse_url(self::DEFAULT_SCHEME . '://' . $this->getOriginal());
                }
                // Prevent php undefined index notices
                if ($parsed === false) {
                    throw new \ErrorException('Malformed URL');
                }
                $parsed += array(
                    'scheme' => null,
                    'host'   => null,
                    'port'   => null,
                    'path'   => null,
                    'query'  => null,
                );
                // Fill all key elements that have an appropriate method
                foreach ($parsed as $element => $value) {
                    $methodName = 'set' . ucfirst($element);
                    if (method_exists($this, $methodName) && $value !== null) {
                        $this->$methodName($value);
                    }
                }
                $this->id = self::$ID;
                self::$ID++;
            } else {
                throw new \ErrorException('Malformed URL');
            }
        } else {
            throw new \InvalidArgumentException('URL must be a string.');
        }
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host argument must be a string.');
        }
        $this->host = $host;
        return $this;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setPort($port = 80)
    {
        $this->port = $port;
        return $this;
    }

    public function getOriginal()
    {
        return $this->original;
    }

    protected function setOriginal($original)
    {
        $this->original = $original;
        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path = '')
    {
        $path = ltrim($path, '/');
        $this->path = $path;
        return $this;
    }

    /**
     * Create path part of a HTTP request
     * @return string
     */
    public function getHttpRequestPath()
    {
        $path = '/' . $this->path . ($this->query ? '?' . $this->query : '');
        $path = preg_replace('/(\/+)/', '/', $path);
        return $path;
    }

    /**
     * Create host part of a HTTP request
     * @return string
     */
    public function getHttpRequestHost($host = null, $implicitPort = true, $preserveTrailingSlash = true)
    {
        // If the original url ends with /, append it
        // otherwise it goes in an endless loop with redirections 
        // that rewrite slashes
        if ($implicitPort) {
            $port = ':' . ((string) $this->getPort() ? : '80');
        } else {
            $port = $this->getPort() ? ':' . $this->getPort() : '';
        }
        $result = ($host ? : $this->host) . $port;
        if ($preserveTrailingSlash) {
            $result .= substr($this->getOriginal(), -1) == '/' ? '/' : '';
        }
        return $result;
    }

    public function __toString()
    {
        return $this->original;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function setParentId($id)
    {
        $this->parentId = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

}
