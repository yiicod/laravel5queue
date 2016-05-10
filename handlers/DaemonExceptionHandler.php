<?php

namespace yiicod\laravel5queue\handlers;

use CLogger;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Yii;

/**
 * DaemonExceptionHandler
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 */
class DaemonExceptionHandler implements ExceptionHandler
{
    public function __construct()
    {
        // automatically send every new message to available log routes
        Yii::getLogger()->autoFlush = 1;
        // when sending a message to log routes, also notify them to dump the message
        // into the corresponding persistent storage (e.g. DB, email)
        Yii::getLogger()->autoDump = true;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        Yii::log($e->getMessage() . ' : ' . $e->getLine() . ' : ' . $e->getFile(), CLogger::LEVEL_ERROR, 'laravel5queue');
    }


    /**
     * Render an exception into an HTTP response.
     *
     * @param  \CHttpRequest $request
     * @param  \Exception $e
     * @return void
     */
    public function render($request, Exception $e)
    {
        return ;
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  \Exception $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        return ;
    }
}
