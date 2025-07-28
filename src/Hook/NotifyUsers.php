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
 
namespace Tobento\App\Task\Hook;

use Psr\Container\ContainerInterface;
use Tobento\App\Task\HookInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\Service\Notifier\UserRecipient;
use Tobento\Service\Schedule\Parameter\AfterTaskHandler;
use Tobento\Service\Schedule\Parameter\BeforeTaskHandler;
use Tobento\Service\Schedule\Parameter\FailedTaskHandler;
use Tobento\Service\Schedule\Parameter;
use function Tobento\App\Translation\trans;

final class NotifyUsers implements HookInterface
{
    /**
     * Create a new NotifyUsers instance.
     *
     * @param string $name
     * @param array<array-key, string> $roles E.g. ['administrator']
     * @param array<array-key, string> $channels E.g. ['mail', 'storage']
     * @param string $notificationSubject
     * @param null|string $queueName
     * @param int $limit
     */
    public function __construct(
        private string $name,
        private array $roles,
        private array $channels,
        private string $notificationSubject = 'Task :status: :name',
        private null|string $queueName = null,
        private int $limit = 100,
    ) {}
    
    /**
     * Returns the hook name.
     *
     * @return string
     */
    public function name(): string
    {
        return trans($this->name);
    }
    
    /**
     * Returns the supported hooks.
     *
     * @return array<array-key, string> E.g. ['after', 'before', 'failed']
     */
    public function supportedHooks(): array
    {
        return ['after', 'before', 'failed'];
    }
    
    /**
     * Create task handler.
     *
     * @param ContainerInterface $container
     * @return AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler
     */
    public function createTaskHandler(ContainerInterface $container): AfterTaskHandler|BeforeTaskHandler|FailedTaskHandler
    {
        $repository = $container->get(UserRepositoryInterface::class);
        
        $recipients = [];
        
        $users = $repository->findAll(
            where: [
                'role_key' => ['in' => $this->roles],
            ],
            limit: $this->limit,
        );
        
        foreach($users as $user) {
            $recipients[] = new UserRecipient($user);
        }
        
        return new Parameter\Notify(
            recipient: $recipients,
            subject: $this->notificationSubject,
            queueName: $this->queueName,
        );
    }
}