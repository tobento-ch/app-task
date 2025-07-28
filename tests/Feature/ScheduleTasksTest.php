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
use Tobento\App\Task\Feature\ScheduleTasks;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\Service\Schedule\ScheduleInterface;

class ScheduleTasksTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Task\Boot\Task::class);
        return $app;
    }
    
    public function testActiveTasksAreScheduled()
    {
        $this->fakeConfig()->with('task.features', [
            ScheduleTasks::class,
        ]);

        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'pausing',
            'registry_id' => 'prune.auth.tokens',
            'app_ids' => ['root'],
        ]);
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'prune.auth.tokens',
            'app_ids' => ['root'],
        ]);
        
        $this->assertSame(1, $app->get(ScheduleInterface::class)->count());
    }
}