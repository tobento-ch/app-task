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

use Psr\Container\ContainerInterface;
use Tobento\Service\Schedule\Parameter\AfterTaskHandler;
use Tobento\Service\Schedule\Parameter\BeforeTaskHandler;
use Tobento\Service\Schedule\Parameter\FailedTaskHandler;

interface HookInterface
{
    /**
     * Returns the hook name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the supported hooks.
     *
     * @return array<array-key, string> E.g. ['after', 'before', 'failed']
     */
    public function supportedHooks(): array;
    
    /**
     * Create task handler.
     *
     * @param ContainerInterface $container
     * @return AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler
     */
    public function createTaskHandler(ContainerInterface $container): AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler;
}