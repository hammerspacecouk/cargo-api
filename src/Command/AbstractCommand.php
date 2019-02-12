<?php
declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractCommand extends Command
{
    protected function getStringArgument(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);
        if (!\is_string($value)) {
            throw new \InvalidArgumentException($name . ' was expected to be a string');
        }
        return $value;
    }
}
