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

use Tobento\App\Task\Exception\HookNotFoundException;

interface HooksInterface
{
    /**
     * Add a hook.
     *
     * @param string $id A unique identifier.
     * @param HookInterface $hook
     * @return static $this
     */
    public function add(string $id, HookInterface $hook): static;
    
    /**
     * Returns true if hook exists, otherwise false.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool;
    
    /**
     * Returns a hook by id.
     *
     * @param string $id
     * @return HookInterface
     * @throws HookNotFoundException
     */
    public function get(string $id): HookInterface;
    
    /**
     * Returns a new instance with the type filtered.
     *
     * @param string $type E.g 'after', 'before', 'failed'
     * @return static
     */
    public function type(string $type): static;
    
    /**
     * Returns all hook names indexed by id.
     *
     * @return array<string, string>
     */
    public function names(): array;
    
    /**
     * Returns all hooks.
     *
     * @return array<string, HookInterface>
     */
    public function all(): array;
}