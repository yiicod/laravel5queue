<?php

namespace yiicod\laravel5queue\handlers;

use Exception;
use Yii;

/**
 * DaemonExceptionHandler
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class FatalThrowableError extends Exception
{
    /**
     * FatalThrowableError constructor.
     *
     * @param string $e
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($e, $code = 0, Exception $previous = null)
    {
        $this->message = $e->getMessage();
        $this->file = $e->getFile();
        $this->line = $e->getLine();
    }
}
