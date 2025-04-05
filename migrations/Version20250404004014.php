<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404004014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, utilisateur_id INT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_67F068BC4B89032C (post_id), INDEX IDX_67F068BCFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sous_commentaire (id INT AUTO_INCREMENT NOT NULL, commentaire_id INT NOT NULL, utilisateur_id INT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_27C91B8FBA9CD190 (commentaire_id), INDEX IDX_27C91B8FFB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC4B89032C FOREIGN KEY (post_id) REFERENCES postes (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaire ADD CONSTRAINT FK_27C91B8FBA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaire ADD CONSTRAINT FK_27C91B8FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C44B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C4FB88E14F
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
            DROP TABLE sous_commentaires
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE commentaires (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, utilisateur_id INT NOT NULL, contenu LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_creation DATETIME NOT NULL, INDEX IDX_D9BEC0C44B89032C (post_id), INDEX IDX_D9BEC0C4FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sous_commentaires (id INT AUTO_INCREMENT NOT NULL, commentaire_id INT NOT NULL, utilisateur_id INT NOT NULL, contenu LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_creation DATETIME NOT NULL, INDEX IDX_662E98A1BA9CD190 (commentaire_id), INDEX IDX_662E98A1FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C44B89032C FOREIGN KEY (post_id) REFERENCES postes (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires ADD CONSTRAINT FK_662E98A1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaires (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires ADD CONSTRAINT FK_662E98A1FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC4B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaire DROP FOREIGN KEY FK_27C91B8FBA9CD190
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaire DROP FOREIGN KEY FK_27C91B8FFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commentaire
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sous_commentaire
        SQL);
    }
}
