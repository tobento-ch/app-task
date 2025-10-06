# App Task

The app task lets you schedule tasks from a web interface.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Task Boot](#task-boot)
        - [Task Config](#task-config)
    - [Features](#features)
        - [Tasks Feature](#tasks-feature)
        - [Task Results Feature](#task-results-feature)
        - [Schedule Tasks Feature](#schedule-tasks-feature)
    - [Available Registries](#available-registries)
        - [Command Task Registry](#command-task-registry)
        - [Task Registry](#task-registry)
    - [Available Hooks](#available-hooks)
        - [Mail Hook](#mail-hook)
        - [Notify Hook](#notify-hook)
        - [Notify Users Hook](#notify-users-hook)
        - [Save Task Result Hook](#save-task-result-hook)
        - [Task Handler Hook](#task-handler-hook)
    - [Running Scheduled Tasks](#running-scheduled-tasks)
    - [Learn More](#learn-more)
        - [Adding Registries Via App](#adding-registries-via-app)
        - [Adding Hooks Via App](#adding-hooks-via-app)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app task project running this command.

```
composer require tobento/app-task
```

## Requirements

- PHP 8.4 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Task Boot

The task boot does the following:

* installs and loads task config
* implements tasks interface
* boots [features](#features) configured in [task config](#task-config)

```php
use Tobento\App\AppFactory;
use Tobento\App\Task\HooksInterface;
use Tobento\App\Task\RegistriesInterface;
use Tobento\App\Task\TaskRepositoryInterface;
use Tobento\App\Task\TaskResultRepositoryInterface;

// Create the app
$app = new AppFactory()->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Task\Boot\Task::class);
$app->booting();

// Implemented interfaces:
$hooks = $app->get(HooksInterface::class);
$registries = $app->get(RegistriesInterface::class);
$taskRepository = $app->get(TaskRepositoryInterface::class);
$taskResultRepository = $app->get(TaskResultRepositoryInterface::class);

// Run the app
$app->run();
```

You may install the [App Backend](https://github.com/tobento-ch/app-backend) and [boot the task](https://github.com/tobento-ch/app-backend#adding-boots) in the backend app.

### Task Config

The configuration for the task is located in the ```app/config/task.php``` file at the default App Skeleton config location where you can configure [features](#features), [task registries](#available-registries), [hooks](#available-hooks) and more.

## Features

### Tasks Feature

The tasks feature provides a tasks page where users can create and schedule tasks using the configured [task registries](#available-registries) and [hooks](#available-hooks).

**Config**

In the [config file](#task-config) you can configure this feature:

```php
'features' => [
    new Feature\Tasks(
        // A menu name to show the tasks link or null if none.
        menu: 'main',
        menuLabel: 'Tasks',
        // A menu parent name (e.g. 'system') or null if none.
        menuParent: null,
        
        // you may disable the ACL while testing for instance,
        // otherwise only users with the right permissions can access the page.
        withAcl: false,
    ),
],
```

**ACL Permissions**

* ```tasks``` User can access tasks
* ```tasks.create``` User can create tasks
* ```tasks.edit``` User can edit tasks
* ```tasks.delete``` User can delete tasks
* ```tasks.run``` User can run tasks

If using the [App Backend](https://github.com/tobento-ch/app-backend), you can assign the permissions in the roles or users page.

### Task Results Feature

The task result feature provides a task results page where users can view task results.

**Config**

In the [config file](#task-config) you can configure this feature:

```php
'features' => [
    new Feature\TaskResults(
        // A menu name to show the tasks link or null if none.
        menu: 'main',
        menuLabel: 'Task Results',
        // A menu parent name (e.g. 'system') or null if none.
        menuParent: null,
        
        // you may disable the ACL while testing for instance,
        // otherwise only users with the right permissions can access the page.
        withAcl: false,
    ),
],
```

**ACL Permissions**

* ```tasks.results``` User can access task results

If using the [App Backend](https://github.com/tobento-ch/app-backend), you can assign the permissions in the roles or users page.

### Schedule Tasks Feature

The schedule tasks feature registers scheduled tasked to the schedule. If you want to stop any scheduled tasks from being processed, uncomment it in the [task config](#task-config), which may be useful in ceratin use cases.

**Config**

In the [config file](#task-config) you can configure this feature:

```php
'features' => [
    Feature\ScheduleTasks::class,
],
```

## Available Registries

### Command Task Registry

The command task registry lets you define [command tasks](https://github.com/tobento-ch/service-schedule#command-task) which can be scheduled by the web interface.

**Config**

In the [config file](#task-config) you can configure this registry:

```php
'registries' => [
    'prune.auth.tokens' => new Registry\CommandTask(
        // Set a name:
        name: 'Command Name',
        
        // Define the command to be executed:
        command: 'command:name',
        
        // You may set command input data:
        input: [
            // passing arguments:
            'username' => 'Tom',
            // with array value:
            'username' => ['Tom', 'Tim'],
            // passing options:
            '--some-option' => 'value',
            // with array value:
            '--some-option' => ['value'],
        ],
        // or you may pass the command, arguments and options as string
        input: 'command:name Tom --bar=1',
        
        // You may add task parameters to be always processed:
        parameters: [
            new \Tobento\Service\Schedule\Parameter\WithoutOverlapping(),
        ],
        
        // Define the supported apps where the task can be run:
        supportedAppIds: ['root', 'backend', 'frontend'],
    ),
]
```

### Task Registry

The task registry lets you define [tasks](https://github.com/tobento-ch/service-schedule#tasks) which can be scheduled by the web interface.

**Config**

In the [config file](#task-config) you can configure this registry:

```php
use Tobento\Service\Schedule\Task;

'registries' => [
    'callable.task' => new Registry\Task(
        // Set a name:
        name: 'A callable task',
        
        // The task to be processed:
        task: new Task\CallableTask(
            callable: static function (): string {
                return 'task output';
            },
        ),
        
        // You may add task parameters to be always processed:
        parameters: [
            new \Tobento\Service\Schedule\Parameter\WithoutOverlapping(),
        ],
        
        // Define the supported apps where the task can be run:
        supportedAppIds: ['root', 'backend', 'frontend'],
    ),
    
    'ping.task' => new Registry\Task(
        name: 'A ping task',
        task: new Task\PingTask(
            uri: 'https://example.com/ping',
            method: 'GET',
        ),
    ),
]
```

## Available Hooks

Hooks can be selected while scheduling tasks for the following events:

* ```before``` running a task
* ```after``` running a task
* when a task ```failed```

> [!CAUTION]
> Hooks events will always be resolved using the app container where [tasks are scheduled](#schedule-tasks-feature), while the main task process might use an app specific container.

and not any specific app container.

### Mail Hook

With the mail hook you can send mails supporting ```before```, ```after``` and ```failed``` events.

**Config**

In the [config file](#task-config) you can configure this hook:

```php
'hooks' => [
    'mail.dev' => new Hook\Mail(
        // Set a name:
        name: 'Mail Developer',
        
        // Define the email to send to:
        email: 'dev@example.com',
        
        // You may customize the mail subject:
        mailSubject: 'Task :status, :id, :name, :description',
    ),
]
```

### Notify Hook

With the notify hook you can send notifications supporting ```before```, ```after``` and ```failed``` events.

**Config**

In the [config file](#task-config) you can configure this hook:

```php
use Tobento\Service\Notifier\Recipient;

'hooks' => [
    'notify.dev' => new Hook\Notify(
        // Set a name:
        name: 'Notify Developer via Mail and SMS',
        
        // Define the recipient:
        recipient: new Recipient(
            email: 'dev@example.com',
            phone: '15556666666',
            channels: ['mail', 'sms'],
        ),
        
        // You may customize the notification subject:
        notificationSubject: 'Task :status, :id, :name, :description',
        
        // You may set a queue name to send the notification to the queue:
        queueName: 'file', // null default
    ),
]
```

You may check out the [App Notifier](https://github.com/tobento-ch/app-notifier) and [Service Notifier](https://github.com/tobento-ch/service-notifier) to learn more.

Make sure you have installed the [App Queue](https://github.com/tobento-ch/app-queue) if queueing notifications.

### Notify Users Hook

With the notify users hook you can send notifications to users using the [User Repository](https://github.com/tobento-ch/app-user#user-repositories-and-factories) and supporting ```before```, ```after``` and ```failed``` events.

**Config**

In the [config file](#task-config) you can configure this hook:

```php
'hooks' => [
    'notify.administrators' => new Hook\NotifyUsers(
        // Set a name:
        name: 'Notify Administrators Via Account',
        
        // Define the roles:
        roles: ['administrator'],
        
        // Define the channels to be notified:
        channels: ['storage'],
        
        // You may set a notification subject:
        notificationSubject: 'www.example.com {task.name}',
        
        // You may change the max limit of users:
        limit: 100, // default
        
        // You may set a queue name to send the notification to the queue:
        queueName: 'file', // null default
    ),
]
```

Make sure you have installed the [App Queue](https://github.com/tobento-ch/app-queue) if queueing notifications.

### Save Task Result Hook

With the save task result hook results can be saved and viewed using the [task result feature](#task-result-feature). It supports the ```after``` and ```failed``` event.

**Config**

In the [config file](#task-config) you can configure this hook:

```php
'hooks' => [
    'save.result' => new Hook\SaveTaskResult(name: 'Save To Task Results'),
]
```

### Task Handler Hook

With the task handler hook lets you can add any task handler.

**Config**

In the [config file](#task-config) you can configure this hook:

```php
use Tobento\Service\Schedule\Parameter\SendResultTo;

'hooks' => [
    'log.failed' => new Hook\TaskHandler(
        name: 'Log Failed',
        handler: new SendResultTo(
            file: directory('app').'storage/tasks/failed.log',
        ),
        supportedHooks: ['failed'],
    ),

    'log.success' => new Hook\TaskHandler(
        name: 'Log',
        handler: new SendResultTo(
            file: directory('app').'storage/tasks/success.log',
        ),
        supportedHooks: ['after'],
    ),
]
```

**Available Task Handlers**

* [Mail Parameter](https://github.com/tobento-ch/service-schedule#mail-parameter)
* [Notify Parameter](https://github.com/tobento-ch/service-schedule#notify-parameter)
* [Ping Parameter](https://github.com/tobento-ch/service-schedule#ping-parameter)
* [Send Result To Parameter](https://github.com/tobento-ch/service-schedule#send-result-to-parameter)

## Running Scheduled Tasks

To run the scheduled tasks, add a cron configuration entry to your server that runs the schedule:run command every minute.

```
* * * * * cd /path-to-your-project && php ap schedule:run >> /dev/null 2>&1
```

If using the [App Backend](https://github.com/tobento-ch/app-backend):

```
* * * * * cd /path-to-your-project/apps/backend && php ap schedule:run >> /dev/null 2>&1
```

## Learn More

### Adding Registries Via App

In addition to add registries via [config file](#task-config), you can use the [App on](https://github.com/tobento-ch/app#on) method to add registries only on demand:

```php
use Tobento\App\Task\RegistriesInterface;
use Tobento\App\Task\Registry\CommandTask;

$app->on(
    RegistriesInterface::class,
    static function(RegistriesInterface $registries): void {
        $registries->add(id: 'prune.auth.tokens', registry: new CommandTask(
            name: 'Prune Auth Tokens',
            command: 'auth:purge-tokens',
        ));
    }
);
```

### Adding Hooks Via App

In addition to add hooks via [config file](#task-config), you can use the [App on](https://github.com/tobento-ch/app#on) method to add hooks only on demand:

```php
use Tobento\App\Task\HooksInterface;
use Tobento\App\Task\Hook\Mail;

$app->on(
    HooksInterface::class,
    static function(HooksInterface $hooks): void {
        $hooks->add(id: 'mail.dev', registry: new Mail(
            name: 'Mail Developer',
            email: 'dev@example.com',
        ));
    }
);
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)