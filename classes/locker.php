<?php
 /**
 * Class Locker - prevent concurrent execution.
 */
class locker
{
    private LOCK = 'locked';
    private UNLOCKED = 'unlocked';
    private FILENAME = 'status.json';

    /**
     * Determine if unlocked.
     *
     * @return bool True if unlocked, False otherwise.
     */
    public function isUnlocked()
    {
        $check = json_decode(file_get_contents($this->FILENAME));

        return $check->status === 'unlocked';
    }

    /**
     * Locking accessor.
     *
     * @param string $status (description)
     */
    public function lock($status = $this->LOCKED)
    {
        $this->setStatus($status);
    }

    /**
     * Unlocking accessor.
     *
     * @param string $status (description)
     */
    public function unlock($status = $this->UNLOCKED)
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
