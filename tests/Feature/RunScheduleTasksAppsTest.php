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
}