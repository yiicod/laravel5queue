<?php

namespace yiicod\laravel5queue;

use CApplicationComponent;
use Illuminate\Encryption\Encrypter;
use Illuminate\Queue\Capsule\Manager;
use Yii;
use yiicod\laravel5queue\connectors\AsyncMongoConnector;
use yiicod\laravel5queue\connectors\MongoConnector;

/**
 * Yii component for laravel 5 queues to work with mongodb
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class Laravel5Queue extends CApplicationComponent
{
    /**
     * Available connections
     *
     * @var array
     */
    public $connections = [
        'default' => [
            'driver' => 'mongoQueue',
            'table' => 'YiiJobs',
            'queue' => 'default',
            'expire' => 60,
        ],
        'async' => [
            'driver' => 'asyncMongoQueue',
            'table' => 'YiiJobsAsync',
            'queue' => 'default',
            'expire' => 60,
            'limit' => 15,
            'yiicAlias' => 'application',
            'connectionName' => 'async'
        ]
    ];

    /**
     * Encrypter private key
     *
     * @var string
     */
    public $privateKey = 'rc5lgpue80sr17nx';

    /**
     * QueueManager instance
     *
     * @var
     */
    private $queueManager;

    /**
     * Initialize component
     */
    public function init()
    {
        parent::init();

        $this->connect();
    }

    /**
     * Connect queue manager for mongo database
     *
     * @return Manager
     */
    public function connect()
    {
        $this->queueManager = new Manager();

        //Some drivers need it
        $this->queueManager->getContainer()->bind('encrypter', function () {
            return new Encrypter($this->privateKey);
        });

        //One more bind for closure functions
        $this->queueManager->getContainer()->bind('Illuminate\Contracts\Encryption\Encrypter', 'encrypter');

        //Connector to successful jobs
        $this->queueManager->addConnector('mongoQueue', function () {
            return new MongoConnector(Yii::app()->mongodb);
        });
        $this->queueManager->addConnector('asyncMongoQueue', function () {
            return new AsyncMongoConnector(Yii::app()->mongodb);
        });
        foreach ($this->connections as $name => $params) {
            $this->queueManager->addConnection($params, $name);
        }

        //Set as global to access
        $this->queueManager->setAsGlobal();

        return $this->queueManager;
    }

    /**
     * Push new job to queue
     *
     * @author Virchenko Maksim <muslim1992@gmail.com>
     *
     * @param mixed $handler
     * @param array $data
     * @param string $queue
     * @param string $connection
     */
    public function push($handler, $data = [], $queue = 'default', $connection = 'default')
    {
        Manager::push($handler, $data, $queue, $connection);
    }

    /**
     * Push new job to queue if this job is not exist
     *
     * @param mixed $handler
     * @param array $data
     * @param string $queue
     * @param string $connection
     */
    public function pushUnique($handler, $data = [], $queue = 'default', $connection = 'default')
    {
        if (false === Manager::connection($connection)->exists($handler, $data, $queue)) {
            Manager::push($handler, $data, $queue, $connection);
        }
    }

    /**
     * Push a new an array of jobs onto the queue.
     *
     * @param  array $jobs
     * @param  mixed $data
     * @param  string $queue
     * @param  string $connection
     *
     * @return mixed
     */
    public static function bulk($jobs, $data = '', $queue = null, $connection = null)
    {
        Manager::bulk($jobs, $data, $queue, $connection);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @param  string $connection
     *
     * @return mixed
     */
    public static function later($delay, $job, $data = '', $queue = null, $connection = null)
    {
        Manager::later($delay, $job, $data, $queue, $connection);
    }
}
