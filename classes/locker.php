<?php

class Locker
{
    public function isUnlocked()
    {
        $check = json_decode(file_get_contents('status.json'));
        return $check->status === "unlocked";
    }

    public function lock($status = "locked")
    {
        $this->setStatus($status);
    }

    public function unlock($status = "unlocked")
    {
        $this->setStatus($status);
    }
 
    public function setStatus($status)
    {
        file_put_contents('status.json', json_encode(array('status' => $status, 'time' => date(DATE_ATOM))));
    }
}
