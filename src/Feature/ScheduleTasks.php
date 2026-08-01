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
 
namespace Tobento\App\Task\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Task\RegistriesInterface;
use Tobento\App\Task\ScheduleProcessor;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\App\Task\Task\RegistryTask;
use Tobento\Service\Schedule\ScheduleInterface;
use Tobento\Service\Schedule\ScheduleProcessorInterface;

class ScheduleTasks extends Boot
{
    public const INFO = [
        'boot' => [
            'Schedules tasks',
        ],
    ];
    
    public const BOOT = [
        \Tobento\App\User\Boot\User::class,
    ];
    
    /**
     * Create a new instance.
     *
     * @param bool $findAppRecursive
     */
    public function __construct(
        protected bool $findAppRecursive = false,
    ) {}

    /**
     * Boot application services.
     *
     * @param AppInterface $app
     * @return void
     */
    public function boot(AppInterface $app): void
    {
        $app->set(ScheduleProcessorInterface::class, ScheduleProcessor::class);
        
        $findAppRecursive = $this->findAppRecursive;
        
        $app->on(
            ScheduleInterface::class,
            static function(
                ScheduleInterface $schedule,
                AppInterface $app,
                TaskRepositoryInterface $taskRepository,
                RegistriesInterface $registries,
            ) use ($findAppRecursive): void {
                $tasks = $taskRepository->findAll(where: ['status' => 'active']);
                
                foreach($tasks as $taskEntity) {
                    
                    if (! $registries->has($taskEntity->registryId())) {
                        continue;
                    }
                    
                    $registry = $registries->get($taskEntity->registryId());
                    
                    foreach($taskEntity->appIds() as $appId) {

                        $task = new RegistryTask(
                            app: $app,
                            registry: $registry,
                            taskEntity: $taskEntity,
                            appId: $appId,
                            findAppRecursive: $findAppRecursive,
                        );

                        $schedule->task($task);
                    }
                }
            }
        );
    }
}