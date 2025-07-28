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
use Tobento\App\Task\Task\SaveTaskResultParameter;
use Tobento\Service\Mail\Message;
use Tobento\Service\Schedule\Parameter\AfterTaskHandler;
use Tobento\Service\Schedule\Parameter\BeforeTaskHandler;
use Tobento\Service\Schedule\Parameter\FailedTaskHandler;
use Tobento\Service\Schedule\Parameter;
use function Tobento\App\Translation\trans;

final class SaveTaskResult implements HookInterface
{
    /**
     * Create a new SaveToTaskResult instance.
     *
     * @param string $name
     */
    public function __construct(
        private string $name
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
        return ['after', 'failed'];
    }
    
    /**
     * Create task handler.
     *
     * @param ContainerInterface $container
     * @return AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler
     */
    public function createTaskHandler(ContainerInterface $container): AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler
    {
        return new SaveTaskResultParameter();
    }
}