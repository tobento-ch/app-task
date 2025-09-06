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
use Throwable;
use Tobento\App\AppInterface;
use Tobento\Service\Schedule\TaskInterface;
use Tobento\Service\Schedule\TaskProcessor as BaseTaskProcessor;
use Tobento\Service\Schedule\TaskResult;
use Tobento\Service\Schedule\TaskResultInterface;
use Tobento\Service\Schedule\TaskSkipException;

class TaskProcessor extends BaseTaskProcessor
{
    /**
     * Create a new TaskProcessor.
     *
     * @param AppInterface $app
     * @param AppInterface $rootApp
     */
    public function __construct(
        protected AppInterface $app,
        protected AppInterface $rootApp,
    ) {}

    /**
     * Booting app.
     *
     * @param AppInterface $app
     * @return void
     */
    protected function bootingApp(AppInterface $app): void
    {
        $app->call([\Tobento\App\Boot\Functions::class, 'boot']);
    }
    
    /**
     * Process the task.
     *
     * @param TaskInterface $task
     * @return TaskResultInterface
     */
    public function processTask(TaskInterface $task): TaskResultInterface
    {
        try {
            $this->bootingApp($this->rootApp);
            $this->handleBeforeTask($this->rootApp->container(), $task);
            
            $this->bootingApp($this->app);
            $result = $task->processTask($this->app->container());
            
            $this->bootingApp($this->rootApp);
            $this->handleAfterTask($this->rootApp->container(), $task, $result);
            
            return $result;
        } catch (TaskSkipException $e) {
            $this->bootingApp($this->rootApp);
            return new TaskResult(task: $task, exception: $e);
        } catch (Throwable $e) {
            $result = new TaskResult(task: $task, exception: $e);
            
            $this->bootingApp($this->rootApp);
            $this->handleFailedTask($this->rootApp->container(), $task, $result);
            return $result;
        }
    }
}