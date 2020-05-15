<?php
declare(strict_types=1);

namespace App;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    private bool $debug;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, bool $debug = false)
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
            $e = $exception;
            $previous = [];
            while ($e) {
                $previous[] = sprintf(
                    "%s:%d %s (%d) [%s]",
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage(),
                    $e->getCode(),
                    get_class($e)
                );
                $e = $e->getPrevious();
            }
            $this->logger->critical(
                $exception->getMessage(),
                [
                    'ex' => get_class($exception),
                    'prev' => json_encode($previous, JSON_THROW_ON_ERROR),
                    'trace' => $exception->getTraceAsString(),
                ],
            );
            $response->setContent('Sorry an error occurred. Please try again');
        }
        $event->setResponse($response);
    }
}
