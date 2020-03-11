<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200311154053 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE activite (id INT AUTO_INCREMENT NOT NULL, note_type_id INT NOT NULL, knowledge_id INT DEFAULT NULL, matiere_id INT DEFAULT NULL, user_id INT NOT NULL, periode_id INT NOT NULL, level_id INT DEFAULT NULL, active_in_period TINYINT(1) NOT NULL, comment LONGTEXT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_B875551544EA4809 (note_type_id), INDEX IDX_B8755515E7DC6902 (knowledge_id), INDEX IDX_B8755515F46CD258 (matiere_id), INDEX IDX_B8755515A76ED395 (user_id), INDEX IDX_B8755515F384C1CF (periode_id), INDEX IDX_B87555155FB14BA7 (level_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE activite_level (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classe (id INT AUTO_INCREMENT NOT NULL, type_classe_id INT NOT NULL, titulaire_id INT DEFAULT NULL, implantation_id INT NOT NULL, name VARCHAR(45) NOT NULL, INDEX IDX_8F87BF963B98E419 (type_classe_id), UNIQUE INDEX UNIQ_8F87BF96A10273AA (titulaire_id), INDEX IDX_8F87BF96CE296AF7 (implantation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, address VARCHAR(255) NOT NULL, zip_code VARCHAR(10) NOT NULL, country VARCHAR(150) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ecole (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eleve (id INT AUTO_INCREMENT NOT NULL, last_name VARCHAR(100) NOT NULL, first_name VARCHAR(100) NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eleve_classe (eleve_id INT NOT NULL, classe_id INT NOT NULL, INDEX IDX_564E8557A6CC7B2 (eleve_id), INDEX IDX_564E85578F5EA509 (classe_id), PRIMARY KEY(eleve_id, classe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE eleve_contact (eleve_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_A0D8DD24A6CC7B2 (eleve_id), INDEX IDX_A0D8DD24E7A1254A (contact_id), PRIMARY KEY(eleve_id, contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE implantation (id INT AUTO_INCREMENT NOT NULL, ecole_id INT NOT NULL, name VARCHAR(100) NOT NULL, address VARCHAR(255) NOT NULL, zip_code VARCHAR(10) NOT NULL, country VARCHAR(150) NOT NULL, default_implantation TINYINT(1) NOT NULL, INDEX IDX_16DC60577EF1B1E (ecole_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE knowledge (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE matiere (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, activite_id INT NOT NULL, eleve_id INT NOT NULL, note VARCHAR(45) NOT NULL, date DATE NOT NULL, comment LONGTEXT DEFAULT NULL, INDEX IDX_CFBDFA149B0F88B1 (activite_id), INDEX IDX_CFBDFA14A6CC7B2 (eleve_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, ponderation VARCHAR(255) NOT NULL, coefficient INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE periode (id INT AUTO_INCREMENT NOT NULL, implantation_id INT NOT NULL, name VARCHAR(45) NOT NULL, date_start DATE NOT NULL, date_end DATE NOT NULL, active TINYINT(1) NOT NULL, comment LONGTEXT DEFAULT NULL, INDEX IDX_93C32DF3CE296AF7 (implantation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_classe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(45) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, roles_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, last_name VARCHAR(100) NOT NULL, first_name VARCHAR(100) NOT NULL, active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D64938C751C4 (roles_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_classe (user_id INT NOT NULL, classe_id INT NOT NULL, INDEX IDX_EAD5A4ABA76ED395 (user_id), INDEX IDX_EAD5A4AB8F5EA509 (classe_id), PRIMARY KEY(user_id, classe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B875551544EA4809 FOREIGN KEY (note_type_id) REFERENCES note_type (id)');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B8755515E7DC6902 FOREIGN KEY (knowledge_id) REFERENCES knowledge (id)');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B8755515F46CD258 FOREIGN KEY (matiere_id) REFERENCES matiere (id)');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B8755515A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B8755515F384C1CF FOREIGN KEY (periode_id) REFERENCES periode (id)');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B87555155FB14BA7 FOREIGN KEY (level_id) REFERENCES activite_level (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF963B98E419 FOREIGN KEY (type_classe_id) REFERENCES type_classe (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF96A10273AA FOREIGN KEY (titulaire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE classe ADD CONSTRAINT FK_8F87BF96CE296AF7 FOREIGN KEY (implantation_id) REFERENCES implantation (id)');
        $this->addSql('ALTER TABLE eleve_classe ADD CONSTRAINT FK_564E8557A6CC7B2 FOREIGN KEY (eleve_id) REFERENCES eleve (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE eleve_classe ADD CONSTRAINT FK_564E85578F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE eleve_contact ADD CONSTRAINT FK_A0D8DD24A6CC7B2 FOREIGN KEY (eleve_id) REFERENCES eleve (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE eleve_contact ADD CONSTRAINT FK_A0D8DD24E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE implantation ADD CONSTRAINT FK_16DC60577EF1B1E FOREIGN KEY (ecole_id) REFERENCES ecole (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA149B0F88B1 FOREIGN KEY (activite_id) REFERENCES activite (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14A6CC7B2 FOREIGN KEY (eleve_id) REFERENCES eleve (id)');
        $this->addSql('ALTER TABLE periode ADD CONSTRAINT FK_93C32DF3CE296AF7 FOREIGN KEY (implantation_id) REFERENCES implantation (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64938C751C4 FOREIGN KEY (roles_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE user_classe ADD CONSTRAINT FK_EAD5A4ABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_classe ADD CONSTRAINT FK_EAD5A4AB8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA149B0F88B1');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B87555155FB14BA7');
        $this->addSql('ALTER TABLE eleve_classe DROP FOREIGN KEY FK_564E85578F5EA509');
        $this->addSql('ALTER TABLE user_classe DROP FOREIGN KEY FK_EAD5A4AB8F5EA509');
        $this->addSql('ALTER TABLE eleve_contact DROP FOREIGN KEY FK_A0D8DD24E7A1254A');
        $this->addSql('ALTER TABLE implantation DROP FOREIGN KEY FK_16DC60577EF1B1E');
        $this->addSql('ALTER TABLE eleve_classe DROP FOREIGN KEY FK_564E8557A6CC7B2');
        $this->addSql('ALTER TABLE eleve_contact DROP FOREIGN KEY FK_A0D8DD24A6CC7B2');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14A6CC7B2');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF96CE296AF7');
        $this->addSql('ALTER TABLE periode DROP FOREIGN KEY FK_93C32DF3CE296AF7');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B8755515E7DC6902');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B8755515F46CD258');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B875551544EA4809');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B8755515F384C1CF');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64938C751C4');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF963B98E419');
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B8755515A76ED395');
        $this->addSql('ALTER TABLE classe DROP FOREIGN KEY FK_8F87BF96A10273AA');
        $this->addSql('ALTER TABLE user_classe DROP FOREIGN KEY FK_EAD5A4ABA76ED395');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE activite_level');
        $this->addSql('DROP TABLE classe');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE ecole');
        $this->addSql('DROP TABLE eleve');
        $this->addSql('DROP TABLE eleve_classe');
        $this->addSql('DROP TABLE eleve_contact');
        $this->addSql('DROP TABLE implantation');
        $this->addSql('DROP TABLE knowledge');
        $this->addSql('DROP TABLE matiere');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE note_type');
        $this->addSql('DROP TABLE periode');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE type_classe');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_classe');
    }
}
