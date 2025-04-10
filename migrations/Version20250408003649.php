<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250408003649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE utilisateur CHANGE points points INT NOT NULL, CHANGE nombre_de_gain nombre_de_gain INT NOT NULL, CHANGE balance balance DOUBLE PRECISION NOT NULL, CHANGE profile_picture profilepicture LONGBLOB DEFAULT NULL, CHANGE numero_ticket numeroTicket INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE utilisateur CHANGE points points INT DEFAULT 0 NOT NULL, CHANGE nombre_de_gain nombre_de_gain INT DEFAULT 0 NOT NULL, CHANGE balance balance DOUBLE PRECISION DEFAULT '0' NOT NULL, CHANGE profilepicture profile_picture LONGBLOB DEFAULT NULL, CHANGE numeroTicket numero_ticket INT DEFAULT NULL
        SQL);
    }
}
