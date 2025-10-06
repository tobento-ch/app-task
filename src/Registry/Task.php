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
 
namespace Tobento\App\Task\Registry;

use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Task\Crud\FrequencyFields;
use Tobento\App\Task\HooksInterface;
use Tobento\App\Task\RegistryInterface;
use Tobento\App\Task\Schedule\EntityCronExpression;
use Tobento\App\Task\TaskEntity;
use Tobento\App\Task\TaskEntityInterface;
use Tobento\Service\Schedule\Parameter;
use Tobento\Service\Schedule\ParameterInterface;
use Tobento\Service\Schedule\Task\AbstractTask;
use Tobento\Service\Schedule\Task\Schedule\CronExpression;
use Tobento\Service\Schedule\TaskInterface;
use Tobento\Service\Schedule\TaskScheduleInterface;
use function Tobento\App\Translation\trans;

class Task implements RegistryInterface
{
    /**
     * @var null|TaskEntityInterface
     */
    protected null|TaskEntityInterface $taskEntity = null;
    
    /**
     * Create a new Task instance.
     *
     * @param string $name
     * @param AbstractTask $task
     * @param array<array-key, string> $supportedAppIds
     * @param array<array-key, ParameterInterface> $parameters
     */
    public function __construct(
        protected string $name,
        protected AbstractTask $task,
        protected array $supportedAppIds = ['root'],
        protected array $parameters = [],
    ) {}
    
    /**
     * Returns the registry name.
     *
     * @return string
     */
    public function name(): string
    {
        return trans($this->name);
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\Checkboxes(name: 'app_ids', label: trans('Run Task In App'))
            ->group(trans('Apps'))
            ->options(array_combine($this->supportedAppIds, $this->supportedAppIds))
            ->validate('required|minItems:1');
        
        yield new Field\Select(name: 'data.timezone', label: trans('Timezone'))
            ->group(trans('Schedule'))
            ->options(fn() => array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers()))
            ->selected(value: fn(ClockInterface $c) => $c->now()->getTimezone()->getName(), action: 'edit')
            ->validate('required');
        
        yield new Field\Text(name: 'data.cron', label: trans('Cron Expression'))
            ->group(trans('Schedule'))
            ->validate('string')
            ->infoText(trans('Leave empty to use the frequencies below.'));
        
        yield new FrequencyFields(name: 'frequencies')
            ->group(trans('Frequencies'))
            ->validate('string');

        yield new Field\Radios(name: 'data.monitor', label: trans('Monitor Task'))
            ->group(trans('Options'))
            ->options(['0' => 'No', '1' => 'Yes'])
            ->validate('required')
            ->selected(value: '1', action: 'create|edit')
            ->displayInline();

        yield new Field\Checkboxes(name: 'data.task_before', label: trans('Before running task'))
            ->group(trans('Hooks'))
            ->options(fn(HooksInterface $hooks): array => $hooks->type('before')->names());
        
        yield new Field\Checkboxes(name: 'data.task_after', label: trans('After running task'))
            ->group(trans('Hooks'))
            ->options(fn(HooksInterface $hooks): array => $hooks->type('after')->names());
        
        yield new Field\Checkboxes(name: 'data.task_failed', label: trans('When task failed'))
            ->group(trans('Hooks'))
            ->options(fn(HooksInterface $hooks): array => $hooks->type('failed')->names());
    }
    
    /**
     * Returns the created task schedule.
     *
     * @param TaskEntityInterface $taskEntity
     * @return TaskScheduleInterface
     */
    public function createTaskSchedule(TaskEntityInterface $taskEntity): TaskScheduleInterface
    {
        return new CronExpression(
            expression: (string) new EntityCronExpression($taskEntity),
            timezone: $taskEntity->data('timezone', 'Europe/Berlin'),
        );
    }
    
    /**
     * Create task.
     *
     * @param ContainerInterface $container
     * @param TaskEntityInterface $taskEntity
     * @return TaskInterface
     */
    public function createTask(ContainerInterface $container, TaskEntityInterface $taskEntity): TaskInterface
    {
        $this->taskEntity = $taskEntity;
        
        $task = $this->getTask()
            ->id($taskEntity->taskId())
            ->name($taskEntity->name())
            ->schedule($this->createTaskSchedule($taskEntity));
        
        foreach($this->parameters as $parameter) {
            $task->parameter($parameter);
        }
        
        if ($taskEntity->data('monitor')) {
            $task->parameter(new Parameter\Monitor());
        }
        
        $hooks = $container->get(HooksInterface::class);
        
        foreach($taskEntity->data('task_before', []) as $hookId) {
            if ($hooks->has($hookId)) {
                $hook = $hooks->get($hookId);
                $handler = $hook->createTaskHandler($container);
                $task->parameter(new Parameter\Before($handler));
            }
        }
        
        foreach($taskEntity->data('task_after', []) as $hookId) {
            if ($hooks->has($hookId)) {
                $hook = $hooks->get($hookId);
                $handler = $hook->createTaskHandler($container);
                $task->parameter(new Parameter\After($handler));
            }
        }
        
        foreach($taskEntity->data('task_failed', []) as $hookId) {
            if ($hooks->has($hookId)) {
                $hook = $hooks->get($hookId);
                $handler = $hook->createTaskHandler($container);
                $task->parameter(new Parameter\Failed($handler));
            }
        }
        
        return $task;
    }
    
    /**
     * Returns the task.
     *
     * @return AbstractTask
     */
    protected function getTask(): AbstractTask
    {
        return clone $this->task;
    }
    
    /**
     * Returns the task entity.
     *
     * @return TaskEntityInterface
     */
    protected function taskEntity(): TaskEntityInterface
    {
        return !is_null($this->taskEntity) ? $this->taskEntity : new TaskEntity();
    }    
}