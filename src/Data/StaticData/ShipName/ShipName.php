<?php
declare(strict_types = 1);
namespace App\Data\StaticData\ShipName;

class ShipName
{
    public const PLACEHOLDER_RANDOM = '?';
    public const PLACEHOLDER_EMPTY = '-';

    private const NAMES_POSITION_1 = __DIR__ . '/resource/ship-names-1.txt';
    private const NAMES_POSITION_2 = __DIR__ . '/resource/ship-names-2.txt';

    private static $parsedListCache = [];

    public function getName(?string $firstWord, ?string $secondWord): string
    {
        if ($firstWord === null && $secondWord === null) {
            throw new InvalidNameException('Must have at least one word in the name');
        }
        $nameParts = ['The'];

        if ($firstWord = $this->parseFirstWord($firstWord)) {
            $nameParts[] = $firstWord;
        }

        if ($secondWord = $this->parseSecondWord($secondWord)) {
            $nameParts[] = $secondWord;
        }

        return implode(' ', $nameParts);
    }

    public function getRandomName(): string
    {
        return $this->getName(self::PLACEHOLDER_RANDOM, self::PLACEHOLDER_RANDOM);
    }

    public function parseFirstWord(?string $word): ?string
    {
        return $this->parseWordFromList($word, self::NAMES_POSITION_1);
    }

    public function parseSecondWord(?string $word): ?string
    {
        return $this->parseWordFromList($word, self::NAMES_POSITION_2);
    }

    private function parseWordFromList(?string $word, string $source)
    {
        $word = trim($word);

        if (empty($word) || $word === self::PLACEHOLDER_EMPTY) {
            return null;
        }

        $words = $this->getAvailableWords($source);

        if ($word === self::PLACEHOLDER_RANDOM) {
            return $words[array_rand($words)];
        }

        if (in_array($word, $words)) {
            return $word;
        }

        throw new InvalidNameException($word . ' is not in available list of names');
    }

    private function getAvailableWords(string $sourcePath)
    {
        if (isset(self::$parsedListCache[$sourcePath])) {
            return self::$parsedListCache[$sourcePath];
        }

        $list = file($sourcePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        self::$parsedListCache[$sourcePath] = $list;
        return $list;
    }
}