<?php


use Phinx\Seed\AbstractSeed;

class EmployeeRole extends AbstractSeed
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
                'email'=>$prefix."_employee@example.com",
                'password'=>password_hash('1234',PASSWORD_DEFAULT),
                'active'=>true,
                'fullname'=> 'MANAGER Active'.$prefix,
                'role'=>'MANAGER'
            ],
            [
                'email'=>$prefix."_employee_inactive@example.com",
                'password'=>password_hash('1234',PASSWORD_DEFAULT),
                'active'=>false,
                'fullname'=> 'MANAGER Inactive'.$prefix,
                'role'=>'MANAGER',
                'activation_token'=>substr(base64_encode(random_bytes(12)),0,60),
                'token_expiration'=>\Carbon\Carbon::now()->modify("+24 hours")->format('Y-m-d H:i:s')
            ]
        ];

        $this->insert('users', $data);

        echo("EMPLOYEES SEEDED".PHP_EOL."DEFAULT PASSWORDS FOR ALL USERS ARE 1234".PHP_EOL);
        print_r($data);
    }
}
