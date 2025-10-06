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
use Tobento\App\Task\TaskResultRepositoryInterface;
use function Tobento\App\Translation\trans;

class TaskResultCrudController extends AbstractCrudController
{
    /**
     * Must be unique, lowercase and only of [a-z-] characters.
     */
    public const RESOURCE_NAME = 'task-results';
    
    /**
     * Create a new TaskResultCrudController instance.
     *
     * @param TaskResultRepositoryInterface $repository
     */
    public function __construct(
        TaskResultRepositoryInterface $repository,
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
        if (in_array($action->name(), ['store', 'update'])) {
            return [];
        }
        
        if ($action->name() !== 'index') {
            yield new Field\PrimaryId(name: 'id');
        }
        
        yield new Field\Text(name: 'task_id', label: trans('Task ID'))
            ->type('number')
            ->group(trans('Task'));
        
        yield new Field\Select(name: 'status', label: trans('Status'))
            ->group(trans('Task'))
            ->options([
                'successful' => trans('successful'),
                'failed' => trans('failed'),
                'skipped' => trans('skipped'),
            ])
            ->formatValue(new Field\Formatter\Badge(classes: [
                'successful' => 'text-success',
                'failed' => 'text-error',
                'skipped' => 'text-info',
            ]));
        
        yield new Field\Text(name: 'run_at', label: trans('Run At'))
            ->group(trans('Task'))
            ->formatValue(new Field\Formatter\Date(format: 'EEEE, dd. MMMM yyyy, HH:mm'));
        
        yield new Field\Text(name: 'runtime_seconds', label: trans('Runtime In Seconds'))
            ->group(trans('Task'));
        
        yield new Field\Text(name: 'memory_usage_bytes', label: trans('Memory Usage In Bytes'))
            ->group(trans('Task'));
        
        yield new Field\Textarea(name: 'result', label: trans('Result'))
            ->group(trans('Task'));
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        $editTask = new Button\Link(label: trans('Edit Task'), group: 'entity')
            ->name('edit.task')
            ->linkToRoute('tasks.edit', function(EntityInterface $entity): null|array {
                $taskId = explode(':', $entity->get('task_id', '0'));
                $taskId = $taskId[1] ?? null;
                return is_null($taskId) ? null : ['id' => $taskId];
            });
        
        return [
            new Action\Index(title: trans('Task Results'))
                ->addButton($editTask)
                ->groupButtons(
                    except: ['show'],
                    button: new Button\Dropdown(label: '', icon: 'dots', group: 'entity')
                        ->name('more')
                        ->raw(),
                ),
            
            new Action\Delete(),
            
            new Action\BulkDelete(),
            
            new Action\Show(trans('Task Result')),
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
            
            new Filter\FieldsSortOrder()
                ->addDefault(name: 'run_at', value: 'desc'),
            
            new Filter\ModalButton()->group('header'),
            
            new Filter\Group(name: 'group-columns')->group('modal')->label(trans('Columns'))->open(false),
            
            new Filter\Columns()->group('group-columns'),
            
            new Filter\Group(name: 'group-pagination')->group('modal')->label(trans('Pagination'))->open(false),
            
            new Filter\PaginationItemsPerPage()
                ->group('group-pagination')
                ->open(false),
            
            new Filter\Pagination(maxItemsPerPage: 2000)->group('footer'),
        ];
    }
}