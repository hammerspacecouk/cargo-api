<?php
declare(strict_types=1);

namespace App;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    /**
     * @var bool
     */
    private $debug;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(bool $debug = false, LoggerInterface $logger)
    {
        $this->debug = $debug;
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // You get the exception object from the received event
        $exception = $event->getThrowable();

        // In the dev and test environment we want the default exception handler.
        if ($this->debug) {
            return;
        }

        $response = new Response();
        if ($exception instanceof NotFoundHttpException) {
            $response->setContent('Not found');
        } else {
            $this->logger->critical($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $response->setContent('Sorry an error occurred. Please try again');
        }
        $event->setResponse($response);
    }
}
