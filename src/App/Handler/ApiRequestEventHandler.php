<?php

declare(strict_types=1);

namespace App\App\Handler;

use App\App\Event\ApiRequestEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ApiRequestEventHandler
{
    public function __invoke(ApiRequestEvent $event)
    {
        //        dump($event->toArray());
        //        file_put_contents('api.json', json_encode($event->toArray()));
    }
}
