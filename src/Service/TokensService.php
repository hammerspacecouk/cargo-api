<?php
declare(strict_types = 1);
namespace App\Service;

use App\Config\TokenConfig;
use App\Data\Database\Entity\InvalidToken;
use App\Data\Database\Mapper\MapperFactory;
use App\Data\ID;
use App\Domain\Exception\InvalidTokenException;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;

class TokensService extends AbstractService
{
    public const EXPIRY_ONE_DAY = 'P1D';
    public const EXPIRY_ONE_HOUR = 'PT1H';
    public const EXPIRY_ONE_MINUTE = 'PT1M';
    public const EXPIRY_DEFAULT = self::EXPIRY_ONE_HOUR;

    private $tokenConfig;

    public function __construct(
        EntityManager $entityManager,
        MapperFactory $mapperFactory,
        DateTimeImmutable $currentTime,
        TokenConfig $tokenConfig
    ) {
        parent::__construct(
            $entityManager,
            $mapperFactory,
            $currentTime
        );
        $this->tokenConfig = $tokenConfig;
    }

    public function makeToken(
        array $claims,
        string $expiry = self::EXPIRY_DEFAULT
    ): Token {
        $signer = new Sha256();
        $builder = (new Builder())->setIssuer($this->tokenConfig->getIssuer())
            ->setAudience($this->tokenConfig->getAudience())
            ->setIssuedAt($this->currentTime->getTimestamp())
            ->setId(ID::makeNewID(InvalidToken::class))
            ->setExpiration($this->currentTime->add($this->getExpiry($expiry))->getTimestamp());

        foreach ($claims as $key => $value) {
            $builder->set($key, $value);
        }

        // Now that all the data is present we can sign it
        $builder->sign($signer, $this->tokenConfig->getPrivateKey());

        return $builder->getToken();
    }

    public function parseToken(
        Token $token,
        bool $checkIfInvalid = true
    ): Token {
        $data = new ValidationData();
        $data->setIssuer($this->tokenConfig->getIssuer());
        $data->setAudience($this->tokenConfig->getAudience());
        $data->setCurrentTime($this->currentTime->getTimestamp());

        $signer = new Sha256();
        if (!$token->verify($signer, $this->tokenConfig->getPrivateKey()) ||
            !$token->validate($data) ||
            $this->tokenIsInvalidated($token, $checkIfInvalid)) {
            throw new InvalidTokenException('Token was tampered with or expired');
        }
        return $token;
    }

    public function tokenIsInvalidated($token, $check = true)
    {
        if ($check) {
            return $this->getInvalidTokenRepo()->isInvalid($token);
        }
        return false;
    }

    public function parseTokenFromString(
        string $tokenString,
        bool $checkIfInvalid = true
    ): Token {
        $token = (new Parser())->parse($tokenString);
        return $this->parseToken($token, $checkIfInvalid);
    }

    private function getExpiry(string $expiry = self::EXPIRY_DEFAULT)
    {
        return new DateInterval($expiry);
    }


}