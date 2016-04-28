<?php
/**
 * Class Locker - prevent concurrent execution.
 */
class locker
{
    /**
     * Determine if unlocked.
     *
     * @return bool True if unlocked, False otherwise.
     */
    public function isUnlocked()
    {
        $check = json_decode(file_get_contents('status.json'));

        return $check->status === 'unlocked';
    }

    /**
     * Lock.
     *
     * @param string $status (description)
     */
    public function lock($status = 'locked')
    {
        $this->setStatus($status);
    }

    /**
     * Unlock.
     *
     * @param string $status (description)
     */
    public function unlock($status = 'unlocked')
    {
        $this->setStatus($status);
    }

    /**
     * Do the actual work for lock() and unlock().
     *
     * @param <type> $status (description)
     */
    private function setStatus($status)
    {
        file_put_contents('status.json', json_encode(array('status' => $status, 'time' => date(DATE_ATOM))));
    }
}
