<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190224145636 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE active_effects (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', effect_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', triggered_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', applies_to_port_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', applies_to_ship_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', applies_to_user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', expiry DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', remaining_count INT DEFAULT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_CB4780B463C5923F (triggered_by_id), INDEX IDX_CB4780B4F51A27AD (applies_to_ship_id), INDEX active_effects_expiry (expiry), INDEX active_effects_for_ship (applies_to_ship_id, expiry), INDEX IDX_CB4780B4F5E9B83B (effect_id), INDEX IDX_CB4780B441A53C4C (applies_to_port_id), INDEX IDX_CB4780B49022C545 (applies_to_user_id), INDEX active_effects_for_user (applies_to_user_id, expiry), INDEX active_effects_for_port (applies_to_port_id, expiry), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE alpha_users (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', query_hash VARBINARY(255) NOT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX alpha_user_query_hash (query_hash), UNIQUE INDEX UNIQ_C013CBB6C55BD620 (query_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE authentication_tokens (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', original_creation_time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', last_used DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', expiry DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', digest LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX auth_token_expiry (expiry), INDEX IDX_E3D92D28A76ED395 (user_id), INDEX auth_token_last_used (last_used), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE channels (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', from_port_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', to_port_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', minimum_entry_rank_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', bearing VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, distance INT NOT NULL, minimum_strength INT NOT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_F314E2B6F8711769 (to_port_id), UNIQUE INDEX channels_unique (from_port_id, to_port_id), INDEX IDX_F314E2B6F0B7C933 (from_port_id), INDEX IDX_F314E2B6E3323E02 (minimum_entry_rank_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE clusters (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', name VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', UNIQUE INDEX UNIQ_EC895D3F5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE config (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', value LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:json)\', uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE crate_locations (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', crate_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', port_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', ship_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', is_current TINYINT(1) NOT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_E3CB5B6E76E92A9C (port_id), INDEX crate_location_created (created_at), INDEX IDX_E3CB5B6E1AB338D0 (crate_id), INDEX IDX_E3CB5B6EC256317D (ship_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE crate_types (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', contents LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, abundance INT NOT NULL, value INT NOT NULL, is_goal TINYINT(1) NOT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE crates (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', reserved_for_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', contents LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, value INT NOT NULL, is_goal TINYINT(1) NOT NULL, value_calculation_date DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\', value_change_rate INT NOT NULL, is_destroyed TINYINT(1) NOT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_BD5480769190173B (reserved_for_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE dictionary (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', word VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, context VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX dictionary_context (context), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE effects (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', minimum_rank_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', type ENUM("DEFENCE","OFFENCE","TRAVEL","SPECIAL","SHIELD","BLOCKADE") NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:enum_effects)\', name LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, description LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, odds_of_winning INT NOT NULL, svg LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, value LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:json)\', purchase_cost INT DEFAULT NULL, duration INT DEFAULT NULL, count INT DEFAULT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', order_number INT NOT NULL, display_group ENUM("DEFENCE","OFFENCE","TRAVEL","SPECIAL") NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:enum_effect_display_group)\', INDEX IDX_480B3CAA4BF66E9D (minimum_rank_id), INDEX effect_display_group (display_group), UNIQUE INDEX UNIQ_480B3CAA551F0F81 (order_number), INDEX effect_order (order_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE events (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', actioning_player_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', actioning_ship_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', subject_rank_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', subject_ship_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', subject_port_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', subject_crate_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', subject_effect_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', action VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, value VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_5387574A64E75424 (actioning_ship_id), INDEX IDX_5387574A67D49DDF (subject_ship_id), INDEX IDX_5387574A22CE78B8 (subject_crate_id), INDEX IDX_5387574AB6B82C11 (subject_effect_id), INDEX IDX_5387574ACF4720A4 (actioning_player_id), INDEX IDX_5387574AD394CB2D (subject_rank_id), INDEX IDX_5387574AD36B863E (subject_port_id), INDEX event_time (time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE hints (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', minimum_rank_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', text LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_E95809464BF66E9D (minimum_rank_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE player_ranks (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', name LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, emblem_svg LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, threshold INT NOT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX player_ranks_threshold (threshold), UNIQUE INDEX UNIQ_7494DB52EB7A2A96 (threshold), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE port_visits (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', player_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', port_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', first_visited DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', last_visited DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_6311C9CC76E92A9C (port_id), UNIQUE INDEX port_visit_unique (player_id, port_id), INDEX IDX_6311C9CC99E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ports (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', cluster_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', name VARCHAR(191) NOT NULL COLLATE utf8mb4_unicode_ci, is_safe_haven TINYINT(1) NOT NULL, is_destination TINYINT(1) NOT NULL, is_open TINYINT(1) NOT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_899FD0CDC36A3328 (cluster_id), UNIQUE INDEX UNIQ_899FD0CD5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ship_classes (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', minimum_rank_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', name LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, description LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, strength INT NOT NULL, auto_navigate TINYINT(1) NOT NULL, capacity INT NOT NULL, speed_multiplier DOUBLE PRECISION NOT NULL, is_starter_ship TINYINT(1) NOT NULL, purchase_cost INT NOT NULL, svg LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', order_number INT NOT NULL, INDEX IDX_CC04EA84BF66E9D (minimum_rank_id), UNIQUE INDEX UNIQ_CC04EA8551F0F81 (order_number), INDEX ship_class_order (order_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ship_locations (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', ship_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', port_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', channel_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', is_current TINYINT(1) NOT NULL, reverse_direction TINYINT(1) NOT NULL, entry_time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', exit_time DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\', uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', score_delta INT DEFAULT NULL, INDEX IDX_E16C61D176E92A9C (port_id), INDEX ship_location_entry_time (entry_time), INDEX ship_location_current_exit (is_current, exit_time), INDEX ship_location_current_ship (is_current, ship_id), INDEX IDX_E16C61D1C256317D (ship_id), INDEX IDX_E16C61D172F5A1AA (channel_id), INDEX ship_location_exit_time (exit_time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ships (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', owner_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', ship_class_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', name LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, strength INT NOT NULL, uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_27F71B31D90D3BC5 (ship_class_id), INDEX IDX_27F71B317E3C61F9 (owner_id), INDEX ships_strength (strength), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE used_action_tokens (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', expiry DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\', uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX used_action_tokens_expiry (expiry), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_effects (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', effect_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', collected_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', used_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\', uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_100622A0A76ED395 (user_id), INDEX user_effects_for_user (user_id, used_at), INDEX IDX_100622A0F5E9B83B (effect_id), INDEX user_effects_expiry (used_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE users (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', home_port_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', last_rank_seen_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', query_hash VARBINARY(255) DEFAULT NULL, anonymous_ip_hash VARBINARY(255) DEFAULT NULL, colour VARCHAR(6) NOT NULL COLLATE utf8mb4_unicode_ci, rotation_steps INT NOT NULL, score BIGINT NOT NULL, score_rate BIGINT NOT NULL, score_calculation_time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', uuid VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', permission_level INT DEFAULT 0 NOT NULL, INDEX IDX_1483A5E9C3C9E1C4 (home_port_id), INDEX user_query_hash (query_hash), UNIQUE INDEX UNIQ_1483A5E9C55BD620 (query_hash), INDEX IDX_1483A5E920841008 (last_rank_seen_id), INDEX user_ip_hash (anonymous_ip_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE active_effects');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE alpha_users');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE authentication_tokens');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE channels');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE clusters');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE config');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE crate_locations');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE crate_types');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE crates');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE dictionary');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE effects');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE events');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE hints');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE player_ranks');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE port_visits');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ports');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ship_classes');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ship_locations');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ships');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE used_action_tokens');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_effects');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE users');
    }
}
