<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404001623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE commentaires (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, utilisateur_id INT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_D9BEC0C44B89032C (post_id), INDEX IDX_D9BEC0C4FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE postes (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_5A763C64FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sous_commentaires (id INT AUTO_INCREMENT NOT NULL, commentaire_id INT NOT NULL, utilisateur_id INT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_662E98A1BA9CD190 (commentaire_id), INDEX IDX_662E98A1FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, points INT DEFAULT 0 NOT NULL, nombre_de_gain INT DEFAULT 0 NOT NULL, adresse VARCHAR(255) DEFAULT NULL, telephone VARCHAR(15) DEFAULT NULL, date_inscription DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, role VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C44B89032C FOREIGN KEY (post_id) REFERENCES postes (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postes ADD CONSTRAINT FK_5A763C64FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires ADD CONSTRAINT FK_662E98A1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaires (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires ADD CONSTRAINT FK_662E98A1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C44B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C4FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE postes DROP FOREIGN KEY FK_5A763C64FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires DROP FOREIGN KEY FK_662E98A1BA9CD190
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires DROP FOREIGN KEY FK_662E98A1FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commentaires
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE postes
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sous_commentaires
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
