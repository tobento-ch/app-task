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
 
namespace Tobento\App\Task\Action;

use Psr\Http\Message\ResponseInterface;
use Tobento\App\AppInterface;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\App\Task\RegistriesInterface;
use Tobento\App\Task\Task\RegistryTask;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Schedule\TaskProcessorInterface;
use Tobento\Service\Translation\TranslatorInterface;

class TaskRunAction
{
    /**
     * Runs the task.
     *
     * @param int|string $id The task id to run.
     * @param AppInterface $app
     * @param RegistriesInterface $registries
     * @param TaskRepositoryInterface $taskRepository
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function __invoke(
        int|string $id,
        AppInterface $app,
        RegistriesInterface $registries,
        TaskRepositoryInterface $taskRepository,
        TranslatorInterface $translator,
        RouterInterface $router,
        ResponserInterface $responser,
    ): ResponseInterface {
        if (is_null($taskEntity = $taskRepository->findById($id))) {
            throw new NotFoundException();
        }

        if (! $registries->has($taskEntity->registryId())) {
            throw new NotFoundException();
        }
        
        $registry = $registries->get($taskEntity->registryId());
        
        foreach($taskEntity->appIds() as $appId) {
            
            $task = new RegistryTask(container: $app->container(), registry: $registry, taskEntity: $taskEntity, appId: $appId);
            
            $result = $task->getTaskProcessor()->processTask($task);
            
            switch (true) {
                case $result->isSuccessful():
                    $responser->messages()->add(
                        level: 'success',
                        message: $translator->trans('Task with the ID :id run successfully.', [':id' => $result->task()->getId()]),
                    );
                    break;
                case $result->isFailure():
                    $responser->messages()->add(
                        level: 'error',
                        message: $translator->trans('Task with the ID :id failed.', [':id' => $result->task()->getId()]),
                    );
                    break;
                case $result->isSkipped():
                    $responser->messages()->add(
                        level: 'notice',
                        message: $translator->trans('Task with the ID :id has been skipped.', [':id' => $result->task()->getId()]),
                    );
                    break;
            }
        }
        
        return $responser->redirect($router->url('tasks.index'));
    }
}