<?php

namespace yiicod\laravel5queue\jobs;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use yiicod\laravel5queue\queues\MongoQueue;

/**
 * MongoJob for laravel queue
 * 
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class MongoJob extends Job implements JobContract
{

    /**
     * The database queue instance.
     *
     * @var MongoQueue
     */
    protected $database;

    /**
     * The database job payload.
     *
     * @var \StdClass
     */
    protected $job;

    /**
     * Create a new job instance.
     *
     * @param  Container  $container
     * @param  MongoQueue  $database
     * @param  \StdClass  $job
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, MongoQueue $database, $job, $queue)
    {
        $this->job = $job;
        $this->queue = $queue;
        $this->database = $database;
        $this->container = $container;
        $this->job->attempts = $this->job->attempts + 1;
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $this->resolveAndFire(json_decode($this->job->payload, true));
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->database->deleteReserved($this->queue, (string) $this->job->_id);
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->delete();

        $this->database->release($this->queue, $this->job, $delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->job->attempts;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return (string) $this->job->_id;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->job->payload;
    }

    /**
     * Get the IoC container instance.
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the underlying queue driver instance.
     *
     * @return MongoQueue
     */
    public function getMongoQueue()
    {
        return $this->database;
    }

    /**
     * Get the underlying database job.
     *
     * @return \StdClass
     */
    public function getDatabaseJob()
    {
        return $this->job;
    }

}
