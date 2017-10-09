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
        $headers->setCookie($this->dataStore->makeCookie());
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
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
