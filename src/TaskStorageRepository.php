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
 
namespace Tobento\App\Task;

use Tobento\App\Task\TaskEntityInterface;
use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\Column\ColumnInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Repository\RepositoryReadException;

/**
 * TaskStorageRepository
 */
class TaskStorageRepository extends StorageRepository implements TaskRepositoryInterface
{
    /**
     * Returns the configured columns.
     *
     * @return iterable<ColumnInterface>|ColumnsInterface
     */
    protected function configureColumns(): iterable|ColumnsInterface
    {
        return [
            new Column\Id(),
            new Column\Text('status')->type(length: 100),
            new Column\Text('name'),
            new Column\Text('registry_id'),
            new Column\Json('data'),
            new Column\Json('app_ids'),
        ];
    }
    
    /**
     * Create entity from array.
     *
     * @param array $entity
     * @return TaskEntityInterface
     */
    public function createEntity(array $entity): TaskEntityInterface
    {
        return $this->entityFactory()->createEntityFromArray($entity);
    }
}