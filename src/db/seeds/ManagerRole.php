<?php


use Phinx\Seed\AbstractSeed;

class ManagerRole extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $prefix=time();
        $data=[
            [
                'email'=>$prefix."@example.com",
                'password'=>password_hash('1234',PASSWORD_DEFAULT),
                'active'=>true,
                'fullname'=> 'MANAGER Active'.$prefix,
                'role'=>'MANAGER'
            ],
        ];

        $this->insert('users', $data);

        echo("MANAGER SEEDED".PHP_EOL."DEFAULT PASSWORDS FOR ALL USERS ARE 1234".PHP_EOL);
        print_r($data);
    }
}
