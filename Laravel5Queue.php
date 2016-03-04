<?php

namespace yiicod\laravel5queue;

use CApplicationComponent;
use Illuminate\Encryption\Encrypter;
use Illuminate\Queue\Capsule\Manager;
use Yii;
use yiicod\laravel5queue\connectors\MongoConnector;

/**
 * Yii component for laravel 5 queues to work with mongodb
 * 
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class Laravel5Queue extends CApplicationComponent
{

    private $queueManager;

    /**
     * Initialize
     * 
     * @author Virchenko Maksim <muslim1992@gmail.com>
     */
    public function init()
    {
        parent::init();

        $this->connect();
    }

    /**
     * Connect queue manager for mongo database
     * 
     * @author Virchenko Maksim <muslim1992@gmail.com>
     * @return Manager
     */
    public function connect()
    {
        $this->queueManager = new Manager();

        //Some drivers need it
        $this->queueManager->getContainer()->bind('encrypter', function() {
            return new Encrypter('rc5lgpue80sr17nx');
        });

        //One more bind for closure functions
        $this->queueManager->getContainer()->bind('Illuminate\Contracts\Encryption\Encrypter', 'encrypter');

        //Connector to successful jobs
        $this->queueManager->addConnector('mongoQueue', function () {
            return new MongoConnector(Yii::app()->mongodb, 'YiiJobs');
        });

        //Add connection for manager
        $this->queueManager->addConnection([
            'driver' => 'mongoQueue',
            'table' => 'YiiJobs',
            'queue' => 'default',
            'expire' => 60,
        ], 'default');

        //Set as global to access
        $this->queueManager->setAsGlobal();

        return $this->queueManager;
    }

    /**
     * Push new job to queue
     * 
     * @author Virchenko Maksim <muslim1992@gmail.com>
     * @param type $handler
     * @param type $data
     */
    public function push($handler, $data = [])
    {
        if (is_callable($handler)) {
            $this->queueManager->getConnection()->push($handler);
        } else {
            Manager::push($handler, $data);
        }
    }

}
