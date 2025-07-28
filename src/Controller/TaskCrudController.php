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
 
namespace Tobento\App\Task\Controller;

use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Http\Exception\HttpException;
use Tobento\App\Task\HooksInterface;
use Tobento\App\Task\RegistriesInterface;
use Tobento\App\Task\TaskEntity;
use Tobento\App\Task\TaskRepositoryInterface;
use function Tobento\App\Translation\trans;

class TaskCrudController extends AbstractCrudController
{
    /**
     * Must be unique, lowercase and only of [a-z-] characters.
     */
    public const RESOURCE_NAME = 'tasks';
    
    /**
     * Create a new TaskCrudController instance.
     *
     * @param TaskRepositoryInterface $repository
     * @param RegistriesInterface $registries
     */
    public function __construct(
        TaskRepositoryInterface $repository,
        protected RegistriesInterface $registries,
    ) {
        $this->repository = $repository;
    }
    
    /**
     * Returns the configured fields.
     *
     * @param string $actionName
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        if (in_array($action->name(), ['create', 'store'])) {
            yield Field\PrimaryId::new(name: 'id')
                ->group(trans('Task'));
            
            yield Field\Value::new(name: 'status', label: trans('Status'))
                ->group(trans('Task'))
                ->value('pausing');
            
            yield Field\Text::new(name: 'name')
                ->group(trans('Task'))
                ->validate('required|string|htmlclean|maxLen:200');
            
            yield Field\Select::new(name: 'registry_id', label: trans('Task'))
                ->group(trans('Task'))
                ->options(fn(RegistriesInterface $registries): array => $registries->names())
                ->validate('required');
            return;
        }
        
        if (in_array($action->name(), ['edit', 'update'])) {
            
            $registryId = $action->entity()->get('registry_id');
            
            if (! $this->registries->has($registryId)) {
                throw new HttpException(
                    statusCode: 403,
                    message: trans('Task registry :id not found.', [':id' => $registryId])
                );
            }
            
            $registry = $this->registries->get($registryId);
            
            yield Field\PrimaryId::new(name: 'id');
            
            yield Field\Text::new(name: 'name')
                ->group(trans('Task'))
                ->validate('required|string|htmlclean|maxLen:200');
            
            yield Field\Text::new(name: 'registry_id', label: trans('Task'))
                ->group(trans('Task'))
                ->value($registry->name())
                ->disabled();
            
            yield Field\Select::new(name: 'status', label: trans('Status'))
                ->group(trans('Task'))
                ->options(['active' => trans('Active'), 'pausing' => trans('Pausing')])
                ->validate('required');
            
            foreach($registry->configureFields($action) as $registryField) {
                yield $registryField;
            }
            
            return;
        }
        
        // Index, delete action:
        yield Field\PrimaryId::new(name: 'id');
        
        yield Field\Text::new(name: 'name');
        
        yield Field\Select::new(name: 'status', label: trans('Status'))
            ->options(['active' => trans('Active'), 'pausing' => trans('Pausing')]);
        
        yield Field\Select::new(name: 'registry_id', label: trans('Task'))
            ->options(fn(RegistriesInterface $registries): array => $registries->names());
        
        yield Field\Checkboxes::new(name: 'app_ids', label: trans('Runs In Apps'))
            ->options([]);
        
        yield Field\Value::new(name: 'next_run_times', label: trans('Next Run Times'))
            ->storable(false)
            ->indexable(true)
            ->editable(false)
            ->process(
                action: 'index',
                // you may define additional parameters being resolved by autowiring!
                processor: function (FieldInterface $field, RegistriesInterface $registries): void {

                    $taskEntity = new TaskEntity($field->entity()->toArray());
                    
                    if (! $registries->has($taskEntity->registryId())) {
                        $field->html(''); return;
                    }
                    
                    $registry = $registries->get($taskEntity->registryId());
                    $schedule = $registry->createTaskSchedule($taskEntity);
                    $dates = $schedule->getNextRunDates(now: new \DateTime(), maxNumber: 3);
                    
                    $dates = array_map(function($date): string {
                        return $date->format('Y-m-d H:i:s P');
                    }, $dates);
                    
                    $field->html(implode('<br>', $dates));
                }
            );
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        $runTask = Button\Form::new(label: trans('Run Task'), group: 'entity')
            ->name('runTask')
            //->method('POST')
            ->linkToRoute('tasks.run', function(EntityInterface $entity): array {
                return ['id' => $entity->id()];
            });
        
        $taskResults = Button\Link::new(label: trans('Task Results'), group: 'entity')
            ->name('results')
            ->linkToRoute('task-results.index', function(EntityInterface $entity): array {
                return ['filter' => ['field' => ['task_id' => $entity->get('task_id')]]];
            });
        
        return [
            Action\Index::new(title: trans('Tasks'))
                ->addButton($runTask)
                ->addButton($taskResults)
                ->removeButton('copy')
                ->groupButtons(
                    except: ['edit'],
                    button: Button\Dropdown::new(label: '', icon: 'dots', group: 'entity')
                        ->name('more')
                        ->raw(),
                ),
            Action\Create::new(title: trans('New Task'))
                ->removeButton('copy', 'new'),
            Action\Store::new(),
            Action\Edit::new(title: trans('Edit Task'))
                ->removeButton('copy', 'new'),
            Action\Update::new(),
            Action\Delete::new(),
            Action\BulkDelete::new(),
        ];
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        return [
            ...Filter\Fields::new()->fields($action->fields())->toFilters(),
            Filter\FieldsSortOrder::new(),
            Filter\ModalButton::new()->group('header'),
            Filter\Group::new(name: 'group-columns')->group('modal')->label(trans('Columns'))->open(false),
            Filter\Columns::new()->group('group-columns'),
            Filter\EditableColumns::new('status', 'name')->group('group-columns'),
            Filter\Group::new(name: 'group-pagination')->group('modal')->label(trans('Pagination'))->open(false),
            Filter\PaginationItemsPerPage::new()
                ->group('group-pagination')
                ->open(false),
            Filter\Pagination::new()->group('footer'),
        ];
    }
}