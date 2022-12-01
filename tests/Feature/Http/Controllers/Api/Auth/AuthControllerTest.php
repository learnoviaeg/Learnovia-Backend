<?php

namespace Tests\Feature\app\Http\Controllers\Api\Auth;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use DatabaseMigrations;
    /**
     *
     * @test
     */
    public function login_will_return_Token_if_credentials_are_valid()
    {
        $this->disableExceptionHandling();

        $user = factory(User::class)->create(['password'=>bcrypt('admin')]);

        $responce = $this->json('POST', 'api.auth.login',[
                'username'=>$user->name,
                'password'=>'admin'
                ]);
            
        $response    
            ->assertStatus(200)
            ->assertjson([
                'token'
            ]);
    }

    public function login_will_not_return_Token_if_credential_invalid()
    {
        $this->disableExceptionHandling();
        
        $response = $this->json('POST', 'api.auth.login', [
            'username'    => 'm',
            'password' => 'dumb'
        ]);

        $response
            ->assertStatus(422)
            ->assertjson([
                'error'=>'user_not_found'
            ]);
    }
}
