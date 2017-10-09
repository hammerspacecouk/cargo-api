<?php
declare(strict_types=1);

namespace App\Data\OAuth\Facebook;

use App\Data\OAuth\SessionDataHandler;
use Facebook\PersistentData\PersistentDataInterface;

class PersistentData implements PersistentDataInterface
{
    private $dataHandler;

    public function __construct(SessionDataHandler $dataHandler)
    {
        $this->dataHandler = $dataHandler;
    }

    public function get($key)
    {
        return $this->dataHandler->get($key);
    }

    public function set($key, $value)
    {
        $this->dataHandler->set($key, $value);
    }
}
