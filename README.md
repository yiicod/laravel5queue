Laravel5 Queue
==============
Branch 1.0 for php 5.6
Branch master for php 7.0

First of all add laravel5queue component to Yii config (console and main) like this:
------------------------------------------------------------------------------------
```php
'components' => [
    'laravel5queue' => ['class' => 'yiicod\laravel5queue\Laravel5Queue']
]
```

add to preload:

```php
'preload' => [
    'queueWorker'
]
```

and console command like this:

```php
'commandMap' => [
    'queueWorker' => ['class' => 'yiicod\laravel5queue\commands\WorkerCommand']
]
```

also: component requires "mongodb" (sammaye/mongoyii-php7:*) component to connect mongo database


Adding jobs to queue:
---------------------

Create your own handler which implements yiicod\laravel5queue\base\BaseHandlerInterface 
OR extends yiicod\laravel5queue\handlers\HandlerAbstract and run parent::fire($job, $data) to restart db connection before job

```php
Laravel5Queue::push(<--YOUR HANDLER CLASS NAME->>, $data);
```

Note: $data - additional data (array) to your handler


Start worker:
------------

run worker daemon with console command like this: 
```php
$ php yiic queueWorker start
```
stop worker daemon:
```php
$ php yiic queueWorker stop
```