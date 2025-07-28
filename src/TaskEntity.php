<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Task;

use Tobento\Service\Collection\Collection;

class TaskEntity implements TaskEntityInterface
{
    /**
     * @var Collection
     */
    protected Collection $attributes;
    
    /**
     * Create a new TaskEntity instance.
     *
     * @param array $attributes
     */
    public function __construct(
        array $attributes = [],
    ) {
        $this->attributes = new Collection($attributes);
    }

    /**
     * Returns the id.
     *
     * @return int
     */
    public function id(): int
    {
        return (int)$this->get('id');
    }

    /**
     * Returns the task id.
     *
     * @return string
     */
    public function taskId(): string
    {
        return sprintf('%s:%s', $this->id(), $this->registryId());
    }
    
    /**
     * Returns the status.
     *
     * @return string
     */
    public function status(): string
    {
        return $this->get('status', '');
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->get('name', '');
    }

    /**
     * Returns the registry id.
     *
     * @return string
     */
    public function registryId(): string
    {
        return $this->get('registry_id', '');
    }
    
    /**
     * Returns a data value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function data(string $name, mixed $default = null): mixed
    {
        return $this->get('data.'.$name, $default);
    }
    
    /**
     * Returns the app ids.
     *
     * @return array<array-key, string>
     */
    public function appIds(): array
    {
        return $this->get('app_ids', []);
    }

    /**
     * Returns whether an attribute exists or not.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->attributes->has($name);
    }
    
    /**
     * Returns an attribute value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->attributes->get($name, $default);
    }
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = $this->attributes->toArray();
        $data['task_id'] = $this->taskId();
        return $data;
    }
}