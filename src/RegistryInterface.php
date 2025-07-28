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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Task\TaskEntityInterface;
use Tobento\Service\Schedule\TaskInterface;
use Tobento\Service\Schedule\TaskScheduleInterface;

interface RegistryInterface
{
    /**
     * Returns the registry name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface;
    
    /**
     * Returns the created task schedule.
     *
     * @param TaskEntityInterface $taskEntity
     * @return TaskScheduleInterface
     */
    public function createTaskSchedule(TaskEntityInterface $taskEntity): TaskScheduleInterface;
    
    /**
     * Create task.
     *
     * @param ContainerInterface $container
     * @param TaskEntityInterface $taskEntity
     * @return TaskInterface
     */
    public function createTask(ContainerInterface $container, TaskEntityInterface $taskEntity): TaskInterface;
}