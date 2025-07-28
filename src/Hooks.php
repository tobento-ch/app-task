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

/**
 * Hooks
 */
final class Hooks implements HooksInterface
{
    /**
     * @var array<string, HookInterface>
     */
    private array $hooks = [];
    
    /**
     * Add a hook.
     *
     * @param string $id A unique identifier.
     * @param HookInterface $hook
     * @return static $this
     */
    public function add(string $id, HookInterface $hook): static
    {
        $this->hooks[$id] = $hook;
        return $this;
    }
    
    /**
     * Returns true if hook exists, otherwise false.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->hooks);
    }
    
    /**
     * Returns a hook by id.
     *
     * @param string $id
     * @return HookInterface
     * @throws HookNotFoundException
     */
    public function get(string $id): HookInterface
    {
        if (isset($this->hooks[$id])) {
            return $this->hooks[$id];
        }
        
        throw new HookNotFoundException(hook: $id);
    }
    
    /**
     * Returns a new instance with the type filtered.
     *
     * @param string $type E.g 'after', 'before', 'failed'
     * @return static
     */
    public function type(string $type): static
    {
        $new = clone $this;
        $new->hooks = array_filter($this->hooks, fn(HookInterface $h) => in_array($type, $h->supportedHooks()));
        return $new;
    }
    
    /**
     * Returns all hook names indexed by id.
     *
     * @return array<string, string>
     */
    public function names(): array
    {
        $names = [];
        
        foreach($this->all() as $id => $hook) {
            $names[$id] = $hook->name();
        }
        
        return $names;
    }
    
    /**
     * Returns all hooks.
     *
     * @return array<string, HookInterface>
     */
    public function all(): array
    {
        return $this->hooks;
    }
}