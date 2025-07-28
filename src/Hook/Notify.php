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
use Tobento\Service\Notifier\RecipientInterface;
use Tobento\Service\Schedule\Parameter\AfterTaskHandler;
use Tobento\Service\Schedule\Parameter\BeforeTaskHandler;
use Tobento\Service\Schedule\Parameter\FailedTaskHandler;
use Tobento\Service\Schedule\Parameter;
use function Tobento\App\Translation\trans;

final class Notify implements HookInterface
{
    /**
     * Create a new Notify instance.
     *
     * @param string $name
     * @param private RecipientInterface $recipient
     * @param string $notificationSubject
     * @param null|string $queueName
     */
    public function __construct(
        private string $name,
        private RecipientInterface $recipient,
        private string $notificationSubject = 'Task :status: :name',
        private null|string $queueName = null,
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
        return new Parameter\Notify(
            recipient: $this->recipient,
            subject: $this->notificationSubject,
            queueName: $this->queueName,
        );
    }
}