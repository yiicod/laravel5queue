<?php

namespace yiicod\laravel5queue\queues;

use Carbon\Carbon;
use DateTime;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use MongoDB;
use yiicod\laravel5queue\jobs\MongoJob;

/**
 * MongoQueue for laravel queue with mongodb
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class MongoQueue extends Queue implements QueueContract
{

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The database table that holds the jobs.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default;

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $expire = 60;

    /**
     * Create a new database queue instance.
     *
     * @param  MongoDB connection $mongo
     * @param  string $table
     * @param  string $default
     * @param  int $expire
     * @return void
     */
    public function __construct($mongo, $table, $default = 'default', $expire = 60)
    {
        $this->table = $table;
        $this->expire = $expire;
        $this->default = $default;
        $this->database = $mongo;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushToDatabase(0, $queue, $this->createPayload($job, $data));
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function exists($job, $data = '', $queue = null)
    {
        return null !== $this->database->{$this->table}->findOne([
            'queue' => $queue,
            'payload' => $this->createPayload($job, $data),
        ]);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return $this->pushToDatabase(0, $queue, $payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  DateTime|int $delay
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return void
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->pushToDatabase($delay, $queue, $this->createPayload($job, $data));
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array $jobs
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        $queue = $this->getQueue($queue);

        $availableAt = $this->getAvailableAt(0);

        $records = array_map(function ($job) use ($queue, $data, $availableAt) {
            return $this->buildDatabaseRecord($queue, $this->createPayload($job, $data), $availableAt);
        }, (array)$jobs);

        return $this->database->{$this->table}->insertMany($records);
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param  string $queue
     * @param  \StdClass $job
     * @param  int $delay
     * @return mixed
     */
    public function release($queue, $job, $delay)
    {
        return $this->pushToDatabase($delay, $queue, $job->payload, $job->attempts);
    }

    /**
     * Push a raw payload to the database with a given delay.
     *
     * @param  DateTime|int $delay
     * @param  string|null $queue
     * @param  string $payload
     * @param  int $attempts
     * @return mixed
     */
    protected function pushToDatabase($delay, $queue, $payload, $attempts = 0)
    {
        $attributes = $this->buildDatabaseRecord($this->getQueue($queue), $payload, $this->getAvailableAt($delay), $attempts);

        return $this->database->{$this->table}->insertOne($attributes);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     * @return Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if (!is_null($this->expire)) {
            $this->releaseJobsThatHaveBeenReservedTooLong($queue);
        }

        if ($job = $this->getNextAvailableJob($queue)) {
            $this->markJobAsReserved((string)$job->_id);

            return new MongoJob($this->container, $this, $job, $queue);
        }
    }

    /**
     * Release the jobs that have been reserved for too long.
     *
     * @param  string $queue
     * @return void
     */
    protected function releaseJobsThatHaveBeenReservedTooLong($queue)
    {
        $expired = Carbon::now()->subSeconds($this->expire)->getTimestamp();

        $this->database->{$this->table}
            ->updateMany([
                'queue' => $this->getQueue($queue),
                'reserved' => 1,
                'reserved_at' => ['$lte' => $expired],
            ], [
                '$set' => [
                    'reserved' => 0,
                    'reserved_at' => null,
                ],
                '$inc' => ['attempts' => 1],
            ]);
    }

    /**
     * Get the next available job for the queue.
     *
     * @param  string|null $queue
     * @return \StdClass|null/
     */
    protected function getNextAvailableJob($queue)
    {
        $job = $this->database->{$this->table}
            ->findOneAndUpdate([
                'queue' => $this->getQueue($queue),
                'reserved' => 0,
                'reserved_at' => null,
//                [
                    'available_at' => ['$lte' => $this->getTime()]
//                ]
//                '$or' => [
//                    ['reserved_at' => null],
//                    ['reserved_at' => ['$lte' => $this->getTime()]],
//                ],
            ], [
                '$set' => [
                    'reserved' => 0,
                    'reserved_at' => null,
                ],
            ], [
                'sort' => ['id' => 1],
            ]);

        return $job ? (object)$job : null;
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param  string $id
     * @return void
     */
    protected function markJobAsReserved($id)
    {
        $this->database->{$this->table}->updateOne(['_id' => new MongoDB\BSON\ObjectID($id)], [
            '$set' => [
                'reserved' => 1,
                'reserved_at' => $this->getTime(),
            ],
            //'$inc' => ['attempts' => 1],
        ]);
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string $queue
     * @param  string $id
     * @return void
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->{$this->table}->deleteOne(['_id' => new MongoDB\BSON\ObjectID($id)]);
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param  DateTime|int $delay
     * @return int
     */
    protected function getAvailableAt($delay)
    {
        $availableAt = $delay instanceof DateTime ? $delay : Carbon::now()->addSeconds($delay);

        return $availableAt->getTimestamp();
    }

    /**
     * Create an array to insert for the given job.
     *
     * @param  string|null $queue
     * @param  string $payload
     * @param  int $availableAt
     * @param  int $attempts
     * @return array
     */
    protected function buildDatabaseRecord($queue, $payload, $availableAt, $attempts = 0)
    {
        return [
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => $attempts,
            'reserved' => 0,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => $this->getTime(),
        ];
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the underlying database instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Get the expiration time in seconds.
     *
     * @return int|null
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set the expiration time in seconds.
     *
     * @param  int|null $seconds
     * @return void
     */
    public function setExpire($seconds)
    {
        $this->expire = $seconds;
    }

}
