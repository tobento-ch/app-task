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
 
namespace Tobento\App\Task\Exception;

class RegistryNotFoundException extends TaskException
{
    /**
     * Create a new RegistryNotFoundException.
     *
     * @param string $registry
     */
    public function __construct(
        protected string $registry,
    ) {
        parent::__construct(sprintf('Registry %s not found', $registry));
    }
    
    /**
     * Returns the registry.
     *
     * @return string
     */
    public function registry(): string
    {
        return $this->registry;
    }
}