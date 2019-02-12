<?php
declare(strict_types=1);

namespace Tests\App\Data;

use App\Infrastructure\ApplicationConfig;
use App\Data\FlashDataStore;
use App\Data\TokenProvider;
use App\Domain\ValueObject\Message\Error;
use App\Domain\ValueObject\Message\Info;
use App\Domain\ValueObject\Message\Ok;
use App\Domain\ValueObject\Message\Warning;
use PHPUnit\Framework\Assert;

class FlashDataStoreTest extends \PHPUnit\Framework\TestCase
{
    public function testMessages(): void
    {
        $store = new FlashDataStore(
            $this->createMock(TokenProvider::class),
            $this->createMock(ApplicationConfig::class)
        );
        // expecting the output to put most important at top, so we'll add them in a different order
        $store->addMessage($message1 = new Ok('1'));
        $store->addMessage($message2 = new Warning('2'));
        $store->addMessage($message3 = new Ok('3'));
        $store->addMessage($message4 = new Info('4'));
        $store->addMessage($message5 = new Error('5'));
        $store->addMessage($message6 = new Warning('6'));

        $result = $store->readMessages();

        Assert::assertSame(
            [
                $message5,
                $message2,
                $message6,
                $message4,
                $message1,
                $message3,
            ],
            $result
        );

        // check that it was wiped out after read
        Assert::assertEmpty($store->readMessages());
    }
}
