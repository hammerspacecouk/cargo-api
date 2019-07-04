<?php
declare(strict_types=1);

namespace App\Domain\Exception;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class NoUserHttpException extends UnauthorizedHttpException
{
}
