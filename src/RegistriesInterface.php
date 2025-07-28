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

use Tobento\App\Task\Exception\RegistryNotFoundException;

interface RegistriesInterface
{
    /**
     * Add a registry.
     *
     * @param string $id A unique identifier.
     * @param string|RegistryInterface $registry
     * @return static $this
     */
    public function add(string $id, string|RegistryInterface $registry): static;
    
    /**
     * Returns true if regsitry exists, otherwise false.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool;
    
    /**
     * Returns a registry by id.
     *
     * @param string $id
     * @return RegistryInterface
     * @throws RegistryNotFoundException
     */
    public function get(string $id): RegistryInterface;
    
    /**
     * Returns all registry names indexed by id.
     *
     * @return array<string, string>
     */
    public function names(): array;
    
    /**
     * Returns all registries.
     *
     * @return array<string, RegistryInterface>
     */
    public function all(): array;
}