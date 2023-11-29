<?php

namespace MaplePHP\Nest;

abstract class AbstractProtocol
{

    protected $isStart = false;
    protected $protocol = array();
    protected $protocolID = array();
    protected $statusCode = 200;
    protected $path = array();

    /**
     * Propagate the active returnable data
     * @return callable
     */
    abstract protected function propagateActiveData(): callable;

    /**
     * Find and protocol item with key
     * @param  string|int|float|null $key
     * @return array|false
     */
    public function exists(string|int|float|null $key): array|false
    {
        $key = (string)$key;
        return (isset($this->protocol[$key]) ? $this->protocol[$key] : false);
    }

    /**
     * Find and the URI before the key
     * @param  string $slug
     * @return array|false
     */
    public function before(string $slug): array|false
    {
        return $this->beforeAfter($slug, -1);
    }

    /**
     * Find and the URI after the key
     * @param  string $slug
     * @return array|false
     */
    public function after(string $slug): array|false
    {
        return $this->beforeAfter($slug, 1);
    }

    /**
     * Get Data By slug
     * @param  string $slug (string becouse of addes duplicate preflix)
     * @return array|null
     */
    public function getPart(?string $slug): ?array
    {
        if ($row = $this->exists($slug)) {
            return $row;
        }
        return null;
    }

    /**
     * Get multiple parts
     * @param  array  $arr array with multiple slugs
     * @param  callable|null $call
     * @return array
     */
    public function getMultipleParts(array $arr, ?callable $call = null): array
    {
        $new = array();
        foreach ($arr as $slug) {
            if ($data = $this->getPart($slug)) {
                $new[] = $data;
                if (!is_null($call)) {
                    $call($data);
                }
            }
        }
        return $new;
    }

    /**
     * Propagate protocol data
     * @param array  $vars            Slugs in array
     * @param string|int $identifier    ID
     * @param mixed  $data              Data to be passed on to protocol (will be returned in validation)
     */
    public function add(array $vars, string|int $identifier, mixed $data): void
    {
        $key = end($vars);
        $this->protocol[$key] = array("uri" => $vars, "id" => $identifier, "data" => $data);
        $this->protocolID[$identifier] = $key;
    }
    
     /**
     * Check if a 301 redirect result
     * @param  array  $vars
     * @return bool
     */
    final protected function is301(array $vars): bool
    {
        if (count($vars) !== count($this->path)) {
            return true;
        } else {
            foreach ($vars as $paramKey => $paramVal) {
                if ((empty($this->path[$paramKey]) || (string)$this->path[$paramKey] !== (string)$paramVal) || !in_array($paramVal, $this->path)) {
                    return true;
                }
            }
        }
        return true;
    }

    /**
     * Validate and Propagate start data as active returnable data if is valid
     * @return void
     */
    final protected function validateStartObject(array $vars): void
    {
        if ($this->isStart) {
            if ($home = reset($this->protocol)) {
                $end = is_array($home['uri'] ?? []) ? end($home['uri']) : "";
                if (is_array($home) && isset($home['id']) && (!$vars || ($vars === $end))) {
                    $this->getMultipleParts($home, $this->propagateActiveData());
                } else {
                    $this->statusCode = 404;
                }
            } else {
                $this->statusCode = 404;
            }
        }
    }

    /**
     * Find and the URI after the key
     * @param  string   $slug
     * @param  int      $increment
     * @return array|false
     */
    final public function beforeAfter(string $slug, int $increment = 0): array|false
    {
        $keys = array_keys($this->protocol);
        $searchResult = array_search($slug, $keys);
        $key = (int)$searchResult + $increment;
        if ($searchResult !== false && ($key >= 0) && $key < count($keys)) {
            $find = ($keys[$key] ?? null);
            return $this->exists($find);
        }
        return false;
    }
}
