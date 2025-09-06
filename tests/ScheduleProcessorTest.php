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

namespace Tobento\App\Task\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppFactory;
use Tobento\App\Task\Hooks;
use Tobento\App\Task\HooksInterface;
use Tobento\App\Task\Registries;
use Tobento\App\Task\RegistriesInterface;
use Tobento\App\Task\Registry\Task;
use Tobento\App\Task\ScheduleProcessor;
use Tobento\App\Task\Task\RegistryTask;
use Tobento\App\Task\TaskEntity;
use Tobento\Service\Container\Container;
use Tobento\Service\Schedule\Schedule;
use Tobento\Service\Schedule\Task\CallableTask;
use Tobento\Service\Schedule\TaskProcessor;
use Tobento\Service\Schedule\TaskProcessorInterface;
use Tobento\Service\Schedule\Test\ScheduleProcessorTest as BaseScheduleProcessorTest;

class ScheduleProcessorTest extends BaseScheduleProcessorTest
{
    public function testUsingRegistryTaskProcessor()
    {
        $taskProcessor = new TaskProcessor(container: new Container());
        $processor = new ScheduleProcessor($taskProcessor);
        $schedule = new Schedule(name: 'default');
        
        $app = (new AppFactory)->createApp();
        $app->set(HooksInterface::class, new Hooks());
        $app->set(RegistriesInterface::class, new Registries($app->container()));
        $app->set(TaskProcessorInterface::class, new TaskProcessor(container: $app->container()));
        
        $task = new RegistryTask(
            app: $app,
            registry: new Task(
                name: 'foo',
                task: new CallableTask(
                    callable: static function (RegistriesInterface $registries): string {
                        return 'task output';
                    },
                ),
            ),
            taskEntity: new TaskEntity([]),
            appId: 'root',
        );
        
        $schedule->task($task);
        
        // this fails as RegistriesInterface could not be resolved as using the default task processor.
        $schedule->task((new CallableTask(function(RegistriesInterface $registries) {}))->id('task1'));
        
        $results = $processor->processSchedule(schedule: $schedule, now: new \DateTime('2023-11-14 16:15'));
        $this->assertCount(2, $results);
        $this->assertCount(1, $results->successful());
        $this->assertCount(1, $results->failed());
        $this->assertCount(0, $results->skipped());
    }
}