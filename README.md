#####
First of all add laravel5queue component to Yii config (console and main) like this:

'components' => ['laravel5queue' => ['class' => 'yiicod\laravel5queue\Laravel5Queue']]

and console command like this:

'queueWorker' => ['class' => 'yiicod\laravel5queue\commands\WorkerCommand'],

also: component requires "mongodb" component to connect mongo database

#####
Adding jobs to queue:

1. For callable functions: 
Yii::app()->laravel5queue->push(function($job) { <--YOUR CODE HERE--> });

Note: you have to call $job->delete(); in the end of your function to remove it from database

2. For handlers:

Create your own handler which implements yiicod\laravel5queue\base\BaseHandlerInterface 
OR extends yiicod\laravel5queue\handlers\Handler and run parent::fire($job, $data) to restart db connection before job

Yii::app()->laravel5queue->push(<--YOUR HANDLER CLASS NAME->>, $data);

Note: $data - additional data to your handler

#####
Start worker:

run worker daemon with console command like this: 

$ php yiic queueWorker start

stop worker daemon:

$ php yiic queueWorker stop