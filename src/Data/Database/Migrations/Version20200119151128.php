<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use App\Domain\ValueObject\Colour;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200119151128 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users ADD emblem_svg LONGTEXT NOT NULL, CHANGE colour colour VARCHAR(6) DEFAULT NULL');

        $currentUsers = $this->connection->query('SELECT * FROM users')->fetchAll();
        foreach ($currentUsers as $user) {
            $colour = $user['colour'];
            $emblemFile = \substr($user['uuid'], 0, 1);
            $emblem = \file_get_contents(__DIR__ . '/../../Static/Emblems/' . $emblemFile . '.svg');
            if (!$emblem) {
                throw new \RuntimeException('Could not get file');
            }
            $emblem = \str_replace('#000000', '#' . $colour, $emblem);

            $this->addSql('UPDATE users SET emblem_svg = "' . addslashes($emblem) . '" WHERE uuid = "' . $user['uuid'] . '"');
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users DROP emblem_svg, CHANGE colour colour VARCHAR(6) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
