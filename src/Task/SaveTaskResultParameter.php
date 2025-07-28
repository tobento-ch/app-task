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

use Tobento\App\Task\TaskResultRepositoryInterface;
use Tobento\Service\Schedule\Parameter\AfterTaskHandler;
use Tobento\Service\Schedule\Parameter\FailedTaskHandler;
use Tobento\Service\Schedule\Parameter\Monitor;
use Tobento\Service\Schedule\Parameter\Parameter;
use Tobento\Service\Schedule\TaskResultInterface;

/**
 * Sends the task result to the task result storage.
 */
class SaveTaskResultParameter extends Parameter implements AfterTaskHandler, FailedTaskHandler
{
    /**
     * Create a new SaveTaskResultParameter.
     *
     * @param array $handle
     */
    public function __construct(
        protected array $handle = ['after', 'failed'],
    ) {}
    
    /**
     * Returns the after task handler.
     *
     * @return callable
     */
    public function getAfterTaskHandler(): callable
    {
        return [$this, 'saveResult'];
    }
    
    /**
     * Returns the failed task handler.
     *
     * @return callable
     */
    public function getFailedTaskHandler(): callable
    {
        return [$this, 'saveResult'];
    }
    
    /**
     * Returns the priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 1000005;
    }
    
    /**
     * Saves task result.
     *
     * @param TaskResultInterface $task
     * @param TaskResultRepositoryInterface $repository
     * @return void
     */
    public function saveResult(TaskResultInterface $result, TaskResultRepositoryInterface $repository): void
    {
        $monitor = $result->task()->parameters()->name(Monitor::class)->first();

        $status = match (true) {
            $result->isSuccessful() => 'successful',
            $result->isFailure() => 'failed',
            $result->isSkipped() => 'skipped',
            default => null,
        };

        $exception = (string)$result->exception()?->__toString();
        
        $repository->create([
            'task_id' => $result->task()->getId(),
            'status' => $status,
            'result' => $result->output().$exception,
            'run_at' => $monitor ? $monitor->startedAt() : date('c'),
            'runtime_seconds' => $monitor ? $monitor->runtimeInSeconds() : null,
            'memory_usage_bytes' => $monitor ? $monitor->memoryUsage() : null,
        ]);
    }
}