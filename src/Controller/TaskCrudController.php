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
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        if (in_array($action->name(), ['create', 'store'])) {
            yield new Field\PrimaryId(name: 'id')
                ->group(trans('Task'));
            
            yield new Field\Value(name: 'status', label: trans('Status'))
                ->group(trans('Task'))
                ->value('pausing');
            
            yield new Field\Text(name: 'name')
                ->group(trans('Task'))
                ->validate('required|string|htmlclean|maxLen:200');
            
            yield new Field\Select(name: 'registry_id', label: trans('Task'))
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
            
            yield new Field\PrimaryId(name: 'id');
            
            yield new Field\Text(name: 'name')
                ->group(trans('Task'))
                ->validate('required|string|htmlclean|maxLen:200');
            
            yield new Field\Text(name: 'registry_id', label: trans('Task'))
                ->group(trans('Task'))
                ->value($registry->name())
                ->disabled();
            
            yield new Field\Select(name: 'status', label: trans('Status'))
                ->group(trans('Task'))
                ->options([
                    'active' => trans('active'),
                    'pausing' => trans('pausing'),
                ])
                ->validate('required');
            
            foreach($registry->configureFields($action) as $registryField) {
                yield $registryField;
            }
            
            return;
        }
        
        // Index, delete action:
        yield new Field\PrimaryId(name: 'id');
        
        yield new Field\Text(name: 'name');
        
        yield new Field\Select(name: 'status', label: trans('Status'))
            ->options([
                'active' => trans('active'),
                'pausing' => trans('pausing'),
            ])
            ->formatValue(new Field\Formatter\Badge(classes: [
                'active' => 'text-success',
                'pausing' => 'text-info',
            ]));
        
        yield new Field\Select(name: 'registry_id', label: trans('Task'))
            ->options($this->registries->names());
        
        yield new Field\Checkboxes(name: 'app_ids', label: trans('Runs In Apps'))
            ->options([]);
        
        yield new Field\Value(name: 'next_run_times', label: trans('Next Run Times'))
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
        $runTask = new Button\Form(label: trans('Run Task'), group: 'entity')
            ->name('runTask')
            ->linkToRoute('tasks.run', function(EntityInterface $entity): array {
                return ['id' => $entity->id()];
            });
        
        $taskResults = new Button\Link(label: trans('Task Results'), group: 'entity')
            ->name('results')
            ->linkToRoute('task-results.index', function(EntityInterface $entity): array {
                return ['filter' => ['field' => ['task_id' => $entity->get('task_id')]]];
            });
        
        return [
            new Action\Index(title: trans('Tasks'))
                ->addButton($runTask)
                ->ajaxButtonAction('runTask')
                ->addButton($taskResults)
                ->removeButton('copy')
                ->groupButtons(
                    except: ['edit'],
                    button: new Button\Dropdown(label: '', icon: 'dots', group: 'entity')
                        ->name('more')
                        ->raw(),
                ),
            
            new Action\Create(title: trans('New Task'))
                ->removeButton('copy', 'new'),
            
            new Action\Store(),
            
            new Action\Edit(title: trans('Edit Task'))
                ->removeButton('copy', 'new'),
            
            new Action\Update(),
            
            new Action\Delete(),
            
            new Action\BulkDelete(),
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
            ...new Filter\Fields()->fields($action->fields())->toFilters(),
            
            new Filter\FieldsSortOrder(),
            
            new Filter\ModalButton()->group('header'),
            
            new Filter\Group(name: 'group-columns')->group('modal')->label(trans('Columns'))->open(false),
            
            new Filter\Columns()
                ->group('group-columns')
                ->default('name', 'status', 'registry_id', 'app_ids', 'actions'),
            
            new Filter\EditableColumns('status', 'name')->group('group-columns'),
            
            new Filter\Group(name: 'group-pagination')->group('modal')->label(trans('Pagination'))->open(false),
            
            new Filter\PaginationItemsPerPage()
                ->group('group-pagination')
                ->open(false),
            
            new Filter\Pagination()->group('footer'),
        ];
    }
}