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
 
namespace Tobento\App\Task\Registry;

use Tobento\Service\Schedule\ParameterInterface;
use Tobento\Service\Schedule\Task\CommandTask as Command;
use Tobento\Service\Schedule\Task\AbstractTask;

class CommandTask extends Task
{
    /**
     * Create a new CommandTask instance.
     *
     * @param string $name
     * @param string $command
     * @param string|array $input
     * @param array<array-key, ParameterInterface> $parameters
     * @param array<array-key, string> $supportedAppIds
     */
    public function __construct(
        protected string $name,
        protected string $command,
        protected string|array $input = [],
        protected array $parameters = [],
        protected array $supportedAppIds = ['root'],
    ) {}
    
    /**
     * Returns the task.
     *
     * @return AbstractTask
     */
    protected function getTask(): AbstractTask
    {
        return new Command(command: $this->command, input: $this->input);
    }
}