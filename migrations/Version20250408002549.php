<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250408002549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE categorie (id_categorie INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id_categorie)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE utilisateur ADD id_categorie INT DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD profile_picture LONGBLOB DEFAULT NULL, ADD balance DOUBLE PRECISION DEFAULT '0' NOT NULL, ADD numero_ticket INT DEFAULT NULL, ADD reset_token INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3C9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id_categorie)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1D1C63B3C9486A13 ON utilisateur (id_categorie)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3C9486A13
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE categorie
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1D1C63B3C9486A13 ON utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE utilisateur DROP id_categorie, DROP description, DROP profile_picture, DROP balance, DROP numero_ticket, DROP reset_token
        SQL);
    }
}
