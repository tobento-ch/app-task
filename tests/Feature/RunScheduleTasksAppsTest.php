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

namespace Tobento\App\Task\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\Apps\AppsInterface;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Schedule\Parameter\AfterTaskHandler;
use Tobento\Service\Schedule\Parameter\BeforeTaskHandler;
use Tobento\Service\Schedule\Parameter\FailedTaskHandler;
use Tobento\Service\Schedule\Parameter\Parameter;
use Tobento\Service\Schedule\TaskResultInterface;

class RunScheduleTasksAppsTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Task\Test\App\Backend::class);
        $app->boot(\Tobento\App\Task\Test\App\Frontend::class);
        $app->boot(\Tobento\App\Schedule\Boot\Schedule::class);
        $app->boot(\Tobento\App\User\Boot\User::class);
        $app->booting();
        
        $app = $app->get(AppsInterface::class)->get('backend')->app();
        return $app;
    }
    
    public function testRunsTaskOnAllAppsSpecified()
    {
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'prune.auth.tokens',
            'app_ids' => ['root', 'frontend', 'backend'],
        ]);

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        $output = $executed->output();
        
        $this->assertSame(0, $executed->code());
        $this->assertStringContainsString('Success: task auth:purge-tokens with the id root:1:prune.auth.tokens', $output);
        $this->assertStringContainsString('Success: task auth:purge-tokens with the id frontend:1:prune.auth.tokens', $output);
        $this->assertStringContainsString('Success: task auth:purge-tokens with the id backend:1:prune.auth.tokens', $output);
    }
    
    public function testUsesSpecificApps()
    {
        $events = new TaskEventsParameter();
        $config = $this->fakeConfig();
        $config->with('task.registries', [
            'foo' => new \Tobento\App\Task\Registry\Task(
                name: 'Callable Task',
                supportedAppIds: ['root', 'backend', 'frontend'],
                task: new \Tobento\Service\Schedule\Task\CallableTask(
                    callable: function (
                        \Tobento\App\AppInterface $app,
                    ): string {
                        return $app->id();
                    },
                ),
                parameters: [$events],
            ),
        ]);
        
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'foo',
            'app_ids' => ['root', 'frontend', 'backend'],
        ]);

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        $output = $executed->output();
        
        $this->assertSame([
            'before' => [
                'backend',
                'backend',
                'backend',
            ],
            'after' => [
                'backend:root',
                'backend:frontend',
                'backend:backend',
            ],
        ], $events->output());
        
        $this->assertSame(0, $executed->code());
    }
    
    public function testUsesSpecificAppsIfFailed()
    {
        $events = new TaskEventsParameter();
        $config = $this->fakeConfig();
        $config->with('task.registries', [
            'foo' => new \Tobento\App\Task\Registry\Task(
                name: 'Callable Task',
                supportedAppIds: ['root', 'backend', 'frontend'],
                task: new \Tobento\Service\Schedule\Task\CallableTask(
                    callable: function (
                        \Tobento\App\AppInterface $app,
                    ): string {
                        throw new \Exception('failed');
                    },
                ),
                parameters: [$events],
            ),
        ]);
        
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'foo',
            'app_ids' => ['root', 'frontend', 'backend'],
        ]);

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        $output = $executed->output();
        
        $this->assertSame([
            'before' => [
                'backend',
                'backend',
                'backend',
            ],
            'failed' => [
                'backend',
                'backend',
                'backend',
            ],
        ], $events->output());
        
        $this->assertSame(1, $executed->code());
    }
}

class TaskEventsParameter extends Parameter implements BeforeTaskHandler, AfterTaskHandler, FailedTaskHandler
{
    public function __construct(
        private array $output = [],
    ) {}
    
    public function output(): array
    {
        return $this->output;
    }

    public function getBeforeTaskHandler(): callable
    {
        return [$this, 'beforeTask'];
    }
    
    public function getAfterTaskHandler(): callable
    {
        return [$this, 'afterTask'];
    }
    
    public function getFailedTaskHandler(): callable
    {
        return [$this, 'failedTask'];
    }

    public function beforeTask(AppInterface $app): void
    {
        $this->output['before'][] = $app->id();
    }
    
    public function afterTask(TaskResultInterface $result, AppInterface $app): void
    {
        $this->output['after'][] = $app->id().':'.$result->output();
    }

    public function failedTask(TaskResultInterface $result, AppInterface $app): void
    {
        $this->output['failed'][] = $app->id();
    }
}