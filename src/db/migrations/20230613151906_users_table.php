<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UsersTable extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('users',[ 'id' => 'user_id']);

        $table
            ->addColumn('email','string',['null'=>false,'limit'=>100])
            ->addColumn('fullname','string',['null'=>false,'limit'=>100])
            ->addColumn('password', 'string', ['limit' => 255,'null' => false])
            ->addColumn('active','boolean',['default'=>false])
            ->addColumn('activation_token','string',['null'=>true])
            ->addColumn('token_expiration','timestamp',['null'=>true])
            ->addColumn('role','enum',['values'=>['EMPLOYEE','MANAGER']])
            ->addColumn('last_login','timestamp',['null'=>true])
            ->addColumn('created_timestamp', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('last_update_timestamp', 'timestamp', ['default' => 'CURRENT_TIMESTAMP','update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['email'],['unique'=>true])
            ->create();
    }

    public function down()
    {  
        $this->table('users')->drop()->save();
    }
}
