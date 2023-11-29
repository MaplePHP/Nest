<?php

namespace MaplePHP\Nest\Interfaces;

interface ProtocolInterface
{
    /**
     * Check if current page is the start page
     * @return bool
     */
    public function isStart(): bool;


    /**
     * List all protocol data
     * @return array
     */
    public function getProtocolData(): array;


    /**
     * Shortcut to @getProtocolData
     * Pollyfill dependencies
     * @return array
     */
    public function list(): array;


    /**
     * Get current protocol data
     * @return array
     */
    public function getData(): array;

    /**
     * Get active protocol data
     * @return object|false
     */
    public function getActiveData(): object|false;

    /**
     * Get active status code
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Get active path as string
     * @return string
     */
    public function getActivePath(): string;

    /**
     * Get active paths as array items
     * @return array
     */
    public function getActivePathData(): array;

    /**
     * You can use to custom search specific data in protocol
     * @param  callable $call
     * @return mixed
     */
    public function accessProtocol(callable $call): mixed;

    /**
     * Get URI Path by slug
     * @param  string $slug (string becouse of addes duplicate preflix)
     * @return string|false
     */
    public function getPath(string $slug): string|false;

    /**
     * Get slug by ID
     * @param  string|int $identifier
     * @return string|null
     */
    public function getSlugByID(string|int $identifier): ?string;

    /**
     * Get URI By id
     * @param  string $identifier (string becouse of addes duplicate preflix)
     * @return string|false
     */
    public function getPathByID(string $identifier): string|false;

    /**
     * Get Data By ID
     * @param  string $identifier (string becouse of addes duplicate preflix)
     * @return array|null
     */
    public function getDataByID(string $identifier): ?array;

    /**
     * Get index/position of item in protocol list
     * @param  string $key    Slug name
     * @return int|false      Array item index
     */
    public function index($key): int|false;

    /**
     * Set vars
     * @param  array $vars
     * @return self
     */
    public function setVars(array $vars): self;

    /**
     * Change how to handle start/root page of protocol. Will use argument array instead as param/uri array
     * @param  array  $arr uri array
     * @return self
     */
    public function changeStartVars(?array $arr): self;

    /**
     * Propegate and prepare validation of path and slug structure
     * @param  array|null $vars
     * @return self
     */
    public function load(?array $vars = null): self;
}
