<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class VaccationsDateIndex extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        $this->execute('CREATE UNIQUE INDEX IF NOT EXISTS no_duplicate_vaccations ON vaccations(`user_id`,`from`,aproval_status);');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE vaccations DROP FOREIGN KEY IF EXISTS vaccations_users_fk ;');
        $this->execute('DROP INDEX IF EXISTS no_duplicate_vaccations on vaccations;');

        $table = $this->table('vaccations');
        $table
            ->addForeignKey('user_id', 'users', 'user_id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION', 'constraint'=>'vaccations_users_fk'])
            ->save();
    }
}
