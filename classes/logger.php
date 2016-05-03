<?php
/**
 * Class Logger - ultra simple logger:.
 *
 * logs to 'log' file in working directory
 */
class logger
{
    private $LOGFILE = 'log.json';
    private $OK = 'OK';

    public function write($message, $status = '')
    {
        d('logger::write called');
        if (!$message) {
            return;
        }
        file_put_contents(AP.DS.$this->LOGFILE, implode("\t", array(date(DATE_ATOM), ($status ? $status : $this->OK), $message), FILE_APPEND));
    }
}
