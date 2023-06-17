<?php


use Phinx\Seed\AbstractSeed;

class Vaccations extends AbstractSeed
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
        $stmt = $this->query('SELECT * FROM users where active=true and role=\'MANAGER\'');
        while($user = $stmt->fetch(\PDO::FETCH_ASSOC)){

            $now = \Carbon\Carbon::now()->modify("+".random_int(10,20)." day");
            
            for($i=10;$i>0;$i--){
                $data = [
                    'user_id'=>$user['user_id'],
                    'from'=>$now->format('Y-m-d'),
                    'until'=>$now->modify('+10 days'),
                    'aproval_status'=>array_rand(['PENDING','REJECTED','APPROVED'])
                ];
                try{
                    $this->insert('vaccations', $data);
                }catch(\Exception $e){
                    // Keep it silent and try for anothewr user 
                }
            }
        }
    }
}
