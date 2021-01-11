<?php
declare(strict_types=1);

namespace App\Functions\Classes;

function whoImplements(string $interfaceName, ?array $from = null): array
{
    if (\interface_exists($interfaceName)) {
        if (!$from) {
            $from = \get_declared_classes();
        }

        return \array_filter($from, function ($className) use ($interfaceName) {
            $implements = \class_implements($className);
            if (!$implements) {
                return false;
            }
            return \in_array($interfaceName, $implements, true);
        });
    }
    return [];
}
