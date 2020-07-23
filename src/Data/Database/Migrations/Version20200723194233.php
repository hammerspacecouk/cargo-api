<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200723194233 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_ranks ADD market_credits INT NOT NULL');
        $this->addSql('ALTER TABLE users ADD market_history INT NOT NULL, ADD market_discovery INT NOT NULL, ADD market_economy INT NOT NULL, ADD market_military INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player_ranks DROP market_credits');
        $this->addSql('ALTER TABLE users DROP market_history, DROP market_discovery, DROP market_economy, DROP market_military');
    }
}
