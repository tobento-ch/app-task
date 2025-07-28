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

use DateTimeInterface;
use Tobento\App\Task\Task\RegistryTask;
use Tobento\Service\Schedule\Event;
use Tobento\Service\Schedule\ScheduleInterface;
use Tobento\Service\Schedule\ScheduleProcessor as BaseScheduleProcessor;
use Tobento\Service\Schedule\TaskResults;
use Tobento\Service\Schedule\TaskResultsInterface;

class ScheduleProcessor extends BaseScheduleProcessor
{
    /**
     * Process the schedule.
     *
     * @param ScheduleInterface $schedule
     * @param DateTimeInterface $now
     * @return TaskResultsInterface
     */
    public function processSchedule(ScheduleInterface $schedule, DateTimeInterface $now): TaskResultsInterface
    {
        $results = new TaskResults();
        
        $this->eventDispatcher?->dispatch(new Event\ScheduleStarting($schedule));
        
        foreach($this->getDueTasks($schedule, $now) as $task) {
                        
            $this->eventDispatcher?->dispatch(new Event\TaskStarting($task));

            if ($task instanceof RegistryTask) {
                $result = $task->getTaskProcessor()->processTask($task);
            } else {
                $result = $this->taskProcessor->processTask($task);
            }
            
            $results->add($result);
            
            $this->eventDispatcher?->dispatch(new Event\TaskFinished($result));
        }
        
        $this->eventDispatcher?->dispatch(new Event\ScheduleFinished($schedule, $results));
        
        return $results;
    }
}