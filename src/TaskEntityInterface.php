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

interface TaskEntityInterface
{
    /**
     * Returns the id.
     *
     * @return int
     */
    public function id(): int;

    /**
     * Returns the task id.
     *
     * @return string
     */
    public function taskId(): string;
    
    /**
     * Returns the status.
     *
     * @return string
     */
    public function status(): string;
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Returns the registry id.
     *
     * @return string
     */
    public function registryId(): string;
    
    /**
     * Returns a data value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function data(string $name, mixed $default = null): mixed;
    
    /**
     * Returns the app ids.
     *
     * @return array<array-key, string>
     */
    public function appIds(): array;

    /**
     * Returns whether an attribute exists or not.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;
    
    /**
     * Returns an attribute value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed;
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array;
}