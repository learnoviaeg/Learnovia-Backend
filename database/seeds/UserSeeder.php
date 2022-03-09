<?php

use Illuminate\Database\Seeder;
use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use GuzzleHttp\Client;
use App\Exports\ExportRoleWithPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Auth\RequestGuard;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $clientt = new Client();
            $data = array(
                'name' => 'Learnovia Company', 
                'meta_data' => array(
                    "image_link" => null,
                    'role'=> 'Super Admin'
                ),
            );

            $data = json_encode($data);

            $res = $clientt->request('POST', 'https://us-central1-learnovia-notifications.cloudfunctions.net/createUser', [
                'headers'   => [
                    'Content-Type' => 'application/json'
                ], 
                'body' => $data
            ]);

        $user = User::create([
            'firstname' => 'Learnovia',
            'lastname' => 'Company',
            'username' => 'Admin',
            'email' => 'admin@learnovia.com',
            'password' => bcrypt('Learnovia123'),
            'real_password' => 'Learnovia123',
            'chat_uid' => json_decode($res->getBody(),true)['user_id'],
            'chat_token' => json_decode($res->getBody(),true)['custom_token'],
            'refresh_chat_token' => json_decode($res->getBody(),true)['refresh_token']
        ]);
       //$Super= \Spatie\Permission\Models\Role::create(['guard_name' => 'api', 'name' => 'Super Admin', 'description' => 'System manager that can monitor everything.']);
        //$Super->givePermissionTo(\Spatie\Permission\Models\Permission::where('name', 'not like', '%user/parent-child%')->where('name', 'not like', '%site/course/student%')->where('name', 'not like', 'user/get-my-child')->where('name', 'not like', '%user/get-current-child%')->where('name', 'not like', '%site/show/as-participant%')->get());
        
        $Super = Role::where('name' ,'like' , 'Super Admin')->get();

        //$Super->givePermissionTo(\Spatie\Permission\Models\Permission::where('name', 'not like', '%user/parent-child%')->where('name', 'not like', '%site/course/student%')->where('name', 'not like', 'user/get-my-child')->where('name', 'not like', '%user/get-current-child%')->where('name', 'not like', '%site/show/as-participant%')->get());

        $user->assignRole($Super);
        //Auth::guard('api')->loginUsingId(i);
        //Auth::loginUsingId(1);

    }
}
