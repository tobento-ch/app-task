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
 
namespace Tobento\App\Task\Hook;

use Psr\Container\ContainerInterface;
use Tobento\App\Task\HookInterface;
use Tobento\Service\Schedule\Parameter\AfterTaskHandler;
use Tobento\Service\Schedule\Parameter\BeforeTaskHandler;
use Tobento\Service\Schedule\Parameter\FailedTaskHandler;
use function Tobento\App\Translation\trans;

final class TaskHandler implements HookInterface
{
    /**
     * Create a new TaskHandler instance.
     *
     * @param string $name
     * @param AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler $handler
     */
    public function __construct(
        private string $name,
        private AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler $handler,
        private null|array $supportedHooks = null,
    ) {}
    
    /**
     * Returns the hook name.
     *
     * @return string
     */
    public function name(): string
    {
        return trans($this->name);
    }
    
    /**
     * Returns the supported hooks.
     *
     * @return array<array-key, string> E.g. ['after', 'before', 'failed']
     */
    public function supportedHooks(): array
    {
        $supportedHooks = [];
        
        if ($this->handler instanceof AfterTaskHandler) {
            $supportedHooks[] = 'after';
        }
        
        if ($this->handler instanceof BeforeTaskHandler) {
            $supportedHooks[] = 'before';
        }
        
        if ($this->handler instanceof FailedTaskHandler) {
            $supportedHooks[] = 'failed';
        }
        
        if (!empty($this->supportedHooks)) {
            return array_intersect($supportedHooks, $this->supportedHooks);
        }
        
        return $supportedHooks;
    }
    
    /**
     * Create task handler.
     *
     * @param ContainerInterface $container
     * @return AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler
     */
    public function createTaskHandler(ContainerInterface $container): AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler
    {
        return $this->handler;
    }
}