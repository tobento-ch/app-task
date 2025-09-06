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
     * @param RegistriesInterface $registries
     */
    public function __construct(
        TaskResultRepositoryInterface $repository,
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
        if (in_array($action->name(), ['store', 'update'])) {
            return [];
        }
        
        if ($action->name() !== 'index') {
            yield Field\PrimaryId::new(name: 'id');
        }
        
        yield Field\Text::new(name: 'task_id', label: trans('Task ID'))
            ->type('number')
            ->group(trans('Task'));
        
        yield Field\Select::new(name: 'status', label: trans('Status'))
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
        
        yield Field\Text::new(name: 'run_at', label: trans('Run At'))
            ->group(trans('Task'))
            ->formatValue(new Field\Formatter\Date(format: 'EEEE, dd. MMMM yyyy, HH:mm'));
        
        yield Field\Text::new(name: 'runtime_seconds', label: trans('Runtime In Seconds'))
            ->group(trans('Task'));
        
        yield Field\Text::new(name: 'memory_usage_bytes', label: trans('Memory Usage In Bytes'))
            ->group(trans('Task'));
        
        yield Field\Textarea::new(name: 'result', label: trans('Result'))
            ->group(trans('Task'));
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        $editTask = Button\Link::new(label: trans('Edit Task'), group: 'entity')
            ->name('edit.task')
            ->linkToRoute('tasks.edit', function(EntityInterface $entity): null|array {
                $taskId = explode(':', $entity->get('task_id', '0'));
                $taskId = $taskId[1] ?? null;
                return is_null($taskId) ? null : ['id' => $taskId];
            });
        
        return [
            Action\Index::new(title: trans('Task Results'))
                ->addButton($editTask)
                ->groupButtons(
                    except: ['show'],
                    button: Button\Dropdown::new(label: '', icon: 'dots', group: 'entity')
                        ->name('more')
                        ->raw(),
                ),
            
            Action\Delete::new(),
            
            Action\BulkDelete::new(),
            
            Action\Show::new(trans('Task Result')),
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
            
            Filter\Group::new(name: 'group-pagination')->group('modal')->label(trans('Pagination'))->open(false),
            
            Filter\PaginationItemsPerPage::new()
                ->group('group-pagination')
                ->open(false),
            
            Filter\Pagination::new(maxItemsPerPage: 2000)->group('footer'),
        ];
    }
}