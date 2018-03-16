<?php
declare(strict_types=1);

namespace App\Controller\Actions;

use App\Domain\Exception\IllegalMoveException;
use App\Domain\Exception\TokenException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

abstract class AbstractAction
{
    private const HEADERS_NO_CACHE = 'no-cache, no-store, must-revalidate';

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    abstract public function invoke(string $token): array;

    public function __invoke(
        Request $request
    ): Response {
        $this->logger->debug(\get_called_class());
        $tokenString = $this->getTokenDataFromRequest($request);
        try {
            $data = $this->invoke($tokenString);
            return $this->actionResponse($data);
        } catch (TokenException $exception) {
            $this->logger->notice('[ACTION] [INVALID TOKEN] ' . $exception->getMessage());
            return $this->errorResponse($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (IllegalMoveException $exception) {
            $this->logger->notice('[ACTION] [ILLEGAL MOVE] ' . $exception->getMessage());
            return $this->errorResponse('Illegal Move: ' . $exception->getMessage(), Response::HTTP_FORBIDDEN);
        }
    }

    protected function getTokenDataFromRequest(Request $request): string
    {
        return $this->getDataFromRequest($request, 'token');
    }

    private function getDataFromRequest(Request $request, string $dataKey)
    {
        if ($request->getMethod() !== 'POST') {
            throw new MethodNotAllowedHttpException(['POST']);
        }
        if ($request->getContentType() !== 'json') {
            throw new BadRequestHttpException('Must be submitted as JSON');
        }
        $data = \json_decode($request->getContent(), true);
        if (!$data) {
            throw new BadRequestHttpException('Must be submitted as valid JSON');
        }

        if (isset($data[$dataKey])) {
            return $data[$dataKey];
        }

        throw new BadRequestHttpException('Bad data supplied. Could not find ' . $dataKey);
    }

    protected function actionResponse(array $data): JsonResponse
    {
        // todo - figure out different response if it is XHR vs Referer (inc cache headers)

        $response = new JsonResponse($data);
        $response->headers->set('cache-control', self::HEADERS_NO_CACHE);
        return $response;
    }

    protected function errorResponse(string $message, $code = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        $data = [
            'error' => $message
        ];
        $response = new JsonResponse($data, $code);
        $response->headers->set('cache-control', self::HEADERS_NO_CACHE);
        return $response;
    }
}
