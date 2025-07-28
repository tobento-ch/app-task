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
use Tobento\Service\Mail\Message;
use Tobento\Service\Schedule\Parameter\AfterTaskHandler;
use Tobento\Service\Schedule\Parameter\BeforeTaskHandler;
use Tobento\Service\Schedule\Parameter\FailedTaskHandler;
use Tobento\Service\Schedule\Parameter;
use function Tobento\App\Translation\trans;

final class Mail implements HookInterface
{
    /**
     * Create a new Mail instance.
     *
     * @param string $name
     * @param string $email
     * @param null|string $mailSubject
     */
    public function __construct(
        private string $name,
        private string $email,
        private null|string $mailSubject = null,
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
        $message = (new Message())->to($this->email);
        
        if ($this->mailSubject) {
            $message->subject($this->mailSubject);
        }
        
        return new Parameter\Mail(message: $message);
    }
}