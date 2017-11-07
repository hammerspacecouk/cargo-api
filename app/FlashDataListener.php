<?php
declare(strict_types=1);

namespace App;

use App\Data\FlashDataStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FlashDataListener implements EventSubscriberInterface
{
    private $dataStore;

    public function __construct(FlashDataStore $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $this->dataStore->setFromRequest($request);
    }

    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $headers = $event->getResponse()->headers;
        // todo - only set this cookie on non-public cacheable pages
        $headers->setCookie($this->dataStore->makeCookie());
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 0]
            ],
            KernelEvents::RESPONSE => [
                ['onKernelResponse', 0]
            ],
        ];
    }
}