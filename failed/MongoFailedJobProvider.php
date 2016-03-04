<?php

namespace yiicod\laravel5queue\failed;

use Carbon\Carbon;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

/**
 * Mongo provider for failed jobs
 * 
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class MongoFailedJobProvider implements FailedJobProviderInterface
{

    /**
     * The database connection name.
     *
     * @var string
     */
    protected $database;

    /**
     * The database table.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database failed job provider.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $database
     * @param  string  $table
     * @return void
     */
    public function __construct($database, $table)
    {
        $this->database = $database;
        $this->table = $table;
    }

    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @return void
     */
    public function log($connection, $queue, $payload)
    {
        $failed_at = Carbon::now();

        $this->getTable()->insert(compact('connection', 'queue', 'payload', 'failed_at'));
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        $result = [];
        $data = $this->getTable()->find([])->sort(['_id' => -1]);

        foreach ($data as $item) {
            $result[] = $item;
        }
        return $result;
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return array
     */
    public function find($id)
    {
        return $this->getTable()->find(['_id' => new MongoId($id)]);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        return $this->getTable()->remove(['_id' => new MongoId($id)]);
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush()
    {
        $this->getTable()->remove();
    }

    /**
     * Get a new query builder instance for the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        return $this->database->{$this->table};
    }

}
