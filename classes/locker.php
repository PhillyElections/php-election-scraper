<?php
/**
 * Class Locker - prevent concurrent execution.
 */
class locker
{
    /**
     * @var string
     */
    private $LOCKED = 'locked';
    /**
     * @var string
     */
    private $UNLOCKED = 'unlocked';

    /**
     * @param $config
     */
    public function __construct(&$config)
    {
        if (!$config) {
            return new Exception('class locker config object.');
        }
        $this->config = &$config;
    }

    /**
     * Determine if unlocked.
     *
     * @return bool True if unlocked, False otherwise.
     */
    public function isUnlocked()
    {
        $check = json_decode(file_get_contents(AP . DS . $this->config->files->status));

        return $check->status === $this->UNLOCKED;
    }

    /**
     * Locking accessor.
     *
     * @param string $status (description)
     */
    public function lock()
    {
        $this->setStatus($this->LOCKED);
    }

    /**
     * Unlocking accessor.
     *
     * @param string $status (description)
     */
    public function unlock()
    {
        $this->setStatus($this->UNLOCKED);
    }

    /**
     * Do the actual work for lock() and unlock().
     *
     * @param <type> $status (description)
     */
    private function setStatus($status)
    {
        file_put_contents(AP . DS . 'status.json', json_encode(array('status' => $status, 'time' => date(DATE_ATOM))));
    }
}
