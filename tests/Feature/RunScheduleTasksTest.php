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
use Tobento\App\Task\Hook;
use Tobento\App\Task\Registry;
use Tobento\App\Task\Task\SaveTaskResultParameter;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\App\Task\TaskResultRepositoryInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Mail\Message;
use Tobento\Service\Notifier\Notification;
use Tobento\Service\Schedule\Task;

class RunScheduleTasksTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Task\Boot\Task::class);
        return $app;
    }
    
    public function testRunsTaskWithCommandTaskRegistry()
    {
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'prune.auth.tokens',
            'app_ids' => ['root'],
        ]);

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        
        $this->assertSame(0, $executed->code());
        $this->assertStringContainsString('Success: task auth:purge-tokens with the id root:1:prune.auth.tokens', $executed->output());
    }
    
    public function testRunsTaskWithTaskRegistry()
    {
        $this->fakeConfig()->with('task.registries', [
            'callable.task' => new Registry\Task(
                name: 'A callable task',
                task: new Task\CallableTask(
                    callable: static function (): string {
                        return 'task output';
                    },
                ),
                parameters: [
                    new \Tobento\Service\Schedule\Parameter\Monitor(),
                ],
            ),
        ]);

        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'callable.task',
            'app_ids' => ['root'],
        ]);

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        
        $this->assertSame(0, $executed->code());
        $this->assertStringContainsString('Success: task Closure with the id root:1:callable.task', $executed->output());
    }
    
    public function testRunsTaskWithMailHook()
    {
        $this->fakeConfig()->with('task.registries', [
            'callable.task' => new Registry\Task(
                name: 'A callable task',
                task: new Task\CallableTask(
                    callable: static function (): string {
                        return 'task output';
                    },
                ),
            ),
        ]);
        
        $this->fakeConfig()->with('task.hooks', [
            'mail.dev' => new Hook\Mail(name: 'Mail to Developer', email: 'dev@example.com'),
        ]);
        
        $fakeMail = $this->fakeMail();
        
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'callable.task',
            'app_ids' => ['root'],
            'data' => ['task_after' => ['mail.dev']],
        ]);

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        
        $this->assertSame(0, $executed->code());

        $fakeMail->mailer(name: 'default')
            ->sent(Message::class)
            ->assertHasTo('dev@example.com')
            ->assertTimes(1);
    }
    
    public function testRunsTaskWithNotifyHook()
    {
        $this->fakeConfig()->with('task.registries', [
            'callable.task' => new Registry\Task(
                name: 'A callable task',
                task: new Task\CallableTask(
                    callable: static function (): string {
                        return 'task output';
                    },
                ),
            ),
        ]);
        
        $this->fakeConfig()->with('task.hooks', [
            'notify.dev' => new Hook\Notify(
                name: 'Notify via Mail and SMS',
                recipient: new \Tobento\Service\Notifier\Recipient(
                    email: 'dev@example.com',
                    channels: ['mail'],
                ),
            ),
        ]);
        
        $notifier = $this->fakeNotifier();
        
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'callable.task',
            'app_ids' => ['root'],
            'data' => ['task_after' => ['notify.dev']],
        ]);

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        
        $this->assertSame(0, $executed->code());

        $notifier
            ->assertSent(Notification::class)
            ->assertSentTimes(Notification::class, 1);
    }
    
    public function testRunsTaskWithNotifyUsersHook()
    {
        $this->fakeConfig()->with('task.registries', [
            'callable.task' => new Registry\Task(
                name: 'A callable task',
                task: new Task\CallableTask(
                    callable: static function (): string {
                        return 'task output';
                    },
                ),
            ),
        ]);
        
        $this->fakeConfig()->with('task.hooks', [
            'notify.administrators' => new Hook\NotifyUsers(
                name: 'Notify Administrators Via Account',
                roles: ['administrator'],
                channels: ['storage'],
            ),
        ]);
        
        $notifier = $this->fakeNotifier();
        
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'callable.task',
            'app_ids' => ['root'],
            'data' => ['task_after' => ['notify.administrators']],
        ]);
        
        $app->get(UserRepositoryInterface::class)->create([
            'email' => 'dev@example.com',
            'role_key' => 'administrator',
        ]);
        
        $app->get(UserRepositoryInterface::class)->create([
            'email' => 'adm@example.com',
            'role_key' => 'administrator',
        ]);
        
        $app->get(UserRepositoryInterface::class)->create([
            'email' => 'editor@example.com',
            'role_key' => 'editor',
        ]);

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        
        $this->assertSame(0, $executed->code());

        $notifier
            ->assertSent(Notification::class)
            ->assertSentTimes(Notification::class, 2);
    }
    
    public function testRunsTaskWithSaveTaskResultHook()
    {
        $this->fakeConfig()->with('task.registries', [
            'callable.task' => new Registry\Task(
                name: 'A callable task',
                task: new Task\CallableTask(
                    callable: static function (): string {
                        return 'task output';
                    },
                ),
            ),
        ]);
        
        $this->fakeConfig()->with('task.hooks', [
            'save.result' => new Hook\SaveTaskResult(name: 'Save To Task Results'),
        ]);
        
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'callable.task',
            'app_ids' => ['root'],
            'data' => ['task_after' => ['save.result']],
        ]);
        
        $this->assertSame(0, $app->get(TaskResultRepositoryInterface::class)->count());

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        
        $this->assertSame(0, $executed->code());
        
        $this->assertSame(1, $app->get(TaskResultRepositoryInterface::class)->count());
    }
    
    public function testRunsTaskWithTaskHandlerHook()
    {
        $this->fakeConfig()->with('task.registries', [
            'callable.task' => new Registry\Task(
                name: 'A callable task',
                task: new Task\CallableTask(
                    callable: static function (): string {
                        return 'task output';
                    },
                ),
            ),
        ]);
        
        $this->fakeConfig()->with('task.hooks', [
            'save.result' => new Hook\TaskHandler(
                name: 'Save result',
                handler: new SaveTaskResultParameter(),
                supportedHooks: ['after'],
            ),
        ]);
        
        $app = $this->bootingApp();
        $app->get(TaskRepositoryInterface::class)->create([
            'status' => 'active',
            'registry_id' => 'callable.task',
            'app_ids' => ['root'],
            'data' => ['task_after' => ['save.result']],
        ]);
        
        $this->assertSame(0, $app->get(TaskResultRepositoryInterface::class)->count());

        $executed = $app->get(ConsoleInterface::class)->execute(command: 'schedule:run');
        
        $this->assertSame(0, $executed->code());
        
        $this->assertSame(1, $app->get(TaskResultRepositoryInterface::class)->count());
    }
}