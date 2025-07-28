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

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Tobento\App\Task\Exception\RegistryNotFoundException;
use Tobento\Service\Autowire\Autowire;

final class Registries implements RegistriesInterface
{
    /**
     * @var array<string, RegistryInterface>
     */
    private array $registries = [];
    
    /**
     * @var Autowire
     */
    private Autowire $autowire;
    
    /**
     * Create a new Registries instance.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container,
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Add a registry.
     *
     * @param string $id A unique identifier.
     * @param string|RegistryInterface $registry
     * @return static $this
     */
    public function add(string $id, string|RegistryInterface $registry): static
    {
        if (is_string($registry)) {
            $registry = $this->autowire->resolve($registry);
            
            if (! $registry instanceof RegistryInterface) {
                throw new InvalidArgumentException(
                    sprintf('Registry %s should be an instanceof %s', $registry::class, RegistryInterface::class)
                );
            }
        }
        
        $this->registries[$id] = $registry;
        return $this;
    }
    
    /**
     * Returns true if regsitry exists, otherwise false.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->registries);
    }
    
    /**
     * Returns a registry by id.
     *
     * @param string $id
     * @return RegistryInterface
     * @throws RegistryNotFoundException
     */
    public function get(string $id): RegistryInterface
    {
        if (isset($this->registries[$id])) {
            return $this->registries[$id];
        }
        
        throw new RegistryNotFoundException(registry: $id);
    }
    
    /**
     * Returns all registry names indexed by id.
     *
     * @return array<string, string>
     */
    public function names(): array
    {
        $names = [];
        
        foreach($this->all() as $id => $registry) {
            $names[$id] = $registry->name();
        }
        
        return $names;
    }
    
    /**
     * Returns all registries.
     *
     * @return array<string, RegistryInterface>
     */
    public function all(): array
    {
        return $this->registries;
    }
}