<?php

namespace MaplePHP\Nest;

use MaplePHP\Nest\Interfaces\ProtocolInterface;

class Protocol extends AbstractProtocol implements ProtocolInterface
{
    
    protected $data = array();
    protected $vars = array();
    protected $startVars;

    public function __construct()
    {
    }

    /**
     * Check if current page is the start page
     * @return bool
     */
    public function isStart(): bool
    {
        return $this->isStart;
    }

    /**
     * List all protocol data
     * @return array
     */
    public function getProtocolData(): array
    {
        return $this->protocol;
    }

    /**
     * Shortcut to @getProtocolData
     * Pollyfill dependencies
     * @return array
     */
    public function list(): array
    {
        return $this->getProtocolData();
    }

    /**
     * Get current protocol data
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get active protocol data
     * @return object|false
     */
    public function getActiveData(): object|false
    {
        return (count($this->data) > 0) ? end($this->data) : false;
    }

    /**
     * Get active status code
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get active path as string
     * @return string
     */
    public function getActivePath(): string
    {
        return "/" . implode("/", $this->path);
    }

    /**
     * Get active paths as array items
     * @return array
     */
    public function getActivePathData(): array
    {
        return $this->path;
    }

    /**
     * You can use to custom search specific data in protocol
     * @param  callable $call
     * @return mixed
     */
    public function accessProtocol(callable $call): mixed
    {
        foreach ($this->protocol as $row) {
            if ($returnData = $call($row)) {
                return $returnData;
            }
        }
        return null;
    }

    /**
     * Get URI Path by slug
     * @param  string $slug (string becouse of addes duplicate preflix)
     * @return string|false
     */
    public function getPath(string $slug): string|false
    {
        if ($row = $this->exists($slug)) {
            return $row['data']->uri;
        }
        return false;
    }

    /**
     * Get slug by ID
     * @param  string|int $identifier
     * @return string|null
     */
    public function getSlugByID(string|int $identifier): ?string
    {
        return ($this->protocolID[$identifier] ?? null);
    }

    /**
     * Get URI By id
     * @param  string $identifier (string becouse of addes duplicate preflix)
     * @return string|false
     */
    public function getPathByID(string $identifier): string|false
    {
        return $this->getPath((string)$this->getSlugByID($identifier));
    }

    /**
     * Get Data By ID
     * @param  string $identifier (string becouse of addes duplicate preflix)
     * @return array|null
     */
    public function getDataByID(string $identifier): ?array
    {
        $slug = $this->getSlugByID($identifier);
        return $this->getPart($slug);
    }

    /**
     * Get index/position of item in protocol list
     * @param  string $key    Slug name
     * @return int|false      Array item index
     */
    public function index($key): int|false
    {
        return array_search($key, array_keys($this->list()));
    }

    /**
     * Set vars
     * @param  array $vars
     * @return self
     */
    public function setVars(array $vars): self
    {
        $this->vars = $vars;
        return $this;
    }

    /**
     * Change how to handle start/root page of protocol. Will use argument array instead as param/uri array
     * @param  array  $arr uri array
     * @return self
     */
    public function changeStartVars(?array $arr): self
    {
        $this->startVars = is_null($arr) ? [] : $arr;
        return $this;
    }

    /**
     * Propegate and prepare validation of path and slug structure
     * @param  array|null $vars
     * @return self
     */
    public function load(?array $vars = null): self
    {
        $this->data = array();
        if (is_null($vars)) {
            $vars = $this->vars;
        }

        if (is_array($vars)) {
            $last = end($vars);
            $this->getMultipleParts($vars, $this->propagateActiveData());
            // VALIDATE
            if ($last2 = end($this->path)) {
                if ($last !== $last2) {
                    $this->statusCode = 404;
                } elseif ($this->is301($vars)) {
                    $this->statusCode = 301;
                }
            } else {
                $this->isStart = true;
            }
        } else {
            $this->isStart = true;
        }
        
        $this->validateStartObject(is_null($this->startVars) ? $vars : $this->startVars);
        return $this;
    }

    /**
     * Propagate the active returnable data
     * @return callable
     */
    protected function propagateActiveData(): callable
    {
        return function (array $data) {
            $this->path = array_values($data['uri']);
            $this->data[$data['id']] = $data['data'];
        };
    }

}
