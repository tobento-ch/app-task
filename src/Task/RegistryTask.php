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
 
namespace Tobento\App\Task\Task;

use Psr\Container\ContainerInterface;
use Tobento\App\AppInterface;
use Tobento\App\Task\Exception\TaskException;
use Tobento\App\Task\HooksInterface;
use Tobento\App\Task\RegistryInterface;
use Tobento\App\Task\TaskEntityInterface;
use Tobento\App\Task\TaskProcessor;
use Tobento\Apps\AppsInterface;
use Tobento\Service\Schedule\ParameterInterface;
use Tobento\Service\Schedule\ParametersInterface;
use Tobento\Service\Schedule\ScheduleInterface;
use Tobento\Service\Schedule\Task\AbstractTask;
use Tobento\Service\Schedule\TaskInterface;
use Tobento\Service\Schedule\TaskProcessorInterface;
use Tobento\Service\Schedule\TaskResult;
use Tobento\Service\Schedule\TaskResultInterface;
use Tobento\Service\Schedule\TaskScheduleInterface;

final class RegistryTask extends AbstractTask
{
    /**
     * @var TaskInterface
     */
    private TaskInterface $task;
    
    /**
     * @var TaskProcessorInterface
     */
    private TaskProcessorInterface $taskProcessor;
    
    /**
     * Create a new RegistryTask instance.
     *
     * @param ContainerInterface $container
     * @param RegistryInterface $registry
     * @param TaskEntityInterface $taskEntity
     * @param string $appId
     */
    public function __construct(
        AppInterface $app,
        RegistryInterface $registry,
        TaskEntityInterface $taskEntity,
        private string $appId,
    ) {
        $application = $this->getAppById($app->container(), $appId);
        
        $this->task = $registry->createTask(container: $app->container(), taskEntity: $taskEntity);
        
        $this->taskProcessor = new TaskProcessor(app: $application, rootApp: $app);
    }

    /**
     * Returns the task processor.
     *
     * @return TaskProcessorInterface
     */
    public function getTaskProcessor(): TaskProcessorInterface
    {
        return $this->taskProcessor;
    }
    
    /**
     * Returns a unique id for the task.
     *
     * @return string
     */
    public function getId(): string
    {
        return sprintf('%s:%s', $this->appId, $this->task->getId());
    }
    
    /**
     * Returns a task name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->task->getName();
    }
    
    /**
     * Return a description of the task.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->task->getDescription();
    }

    /**
     * Process the task.
     *
     * @param ContainerInterface $container
     * @return TaskResultInterface
     * @throws \Throwable
     */
    public function processTask(ContainerInterface $container): TaskResultInterface
    {
        $result = $this->task->processTask($container);
        
        return new TaskResult(
            task: $this,
            output: $result->output(),
            exception: $result->exception(),
        );
    }
    
    /**
     * Returns the schedule.
     *
     * @return TaskScheduleInterface
     */
    public function getSchedule(): TaskScheduleInterface
    {
        return $this->task->getSchedule();
    }
    
    /**
     * Returns the parameters.
     *
     * @return ParametersInterface
     */
    public function parameters(): ParametersInterface
    {
        return $this->task->parameters();
    }
    
    /**
     * Add a parameter.
     *
     * @param ParameterInterface $parameter
     * @return static $this
     * @psalm-suppress MethodSignatureMismatch
     */
    public function parameter(ParameterInterface $parameter): static
    {
        $this->task->parameter($parameter);
        return $this;
    }
    
    /**
     * Return the app by id.
     *
     * @param ContainerInterface $container
     * @param string $appId
     * @return AppInterface
     * @throws \Throwable
     */
    protected function getAppById(ContainerInterface $container, string $appId): AppInterface
    {
        $app = $container->get(AppInterface::class);
        
        if ($appId === $app->id()) {
            return $app;
        }
        
        if (! $container->has(AppsInterface::class)) {
            throw new TaskException('Apps not exists.');
        }
        
        $apps = $container->get(AppsInterface::class);
        
        if ($appId === 'root') {
            if (! $apps->has($app->id())) {
                throw new TaskException(sprintf('App with the id %s not found.', $app->id()));
            }
            
            return $apps->get($app->id())->rootApp();
        }
        
        if (! $apps->has($appId)) {
            throw new TaskException(sprintf('App with the id %s not found.', $appId));
        }
        
        $app = $apps->get($appId)->app();
        $app->booting();
        return $app;
    }
}