<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Vacations extends AbstractMigration
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
        $table = $this->table('vaccations',[ 'id' => 'vaccation_id']);
        $table
            ->addColumn('user_id','integer',['signed'=>false,'null'=>false])
            ->addColumn('from','date',['null'=>false])
            ->addColumn('until','date',['null'=>false])
            ->addColumn('aproval_status','enum',['values'=>['PENDING','REJECTED','APPROVED'],'null'=>false,'default'=>'PENDING'])
            ->addColumn('comments','text',['null'=>true])
            ->addColumn('request_timestamp', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('user_id', 'users', 'user_id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->save();

    }

    public function down()
    {  
        $this->table('vaccations')->drop()->save();
    }
}
