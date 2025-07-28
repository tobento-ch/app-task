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

namespace Tobento\App\Task\Migration;

use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\App\Task\TaskResultRepositoryInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Repository\Storage\Migration\RepositoryAction;
use Tobento\Service\Repository\Storage\Migration\RepositoryDeleteAction;

class TaskRepositories implements MigrationInterface
{
    /**
     * Create a new TaskRepositories instance.
     *
     * @param TaskRepositoryInterface $taskRepository
     * @param TaskResultRepositoryInterface $taskResultRepository
     */
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
        protected TaskResultRepositoryInterface $taskResultRepository,
    ) {}
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Task repositories.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        return new Actions(
            RepositoryAction::newOrNull(
                repository: $this->taskRepository,
                description: 'Task resource.',
            ),
            RepositoryAction::newOrNull(
                repository: $this->taskResultRepository,
                description: 'Task result resource.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            RepositoryDeleteAction::newOrNull(
                repository: $this->taskRepository,
                description: 'Task resource',
            ),
            RepositoryDeleteAction::newOrNull(
                repository: $this->taskResultRepository,
                description: 'Task result resource',
            ),
        );
    }
}