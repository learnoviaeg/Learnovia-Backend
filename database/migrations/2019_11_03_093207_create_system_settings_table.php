<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateSystemSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key')->unique();
            $table->text('data');
            $table->timestamps();
        });
        DB::table('system_settings')->insert([
            ['key'=> 'languages' ,'data' => serialize([
                ['id' => 0 , 'name' => 'EN' , 'active' => 1 , 'default' => 1] ,
                ['id' => 1 , 'name' => 'FR' , 'active' => 1 , 'default' => 0] ,
                ['id' => 2 , 'name' => 'AR' , 'active' => 1 , 'default' => 0] ,
                ['id' => 3 , 'name' => 'DE' , 'active' => 1 , 'default' => 0] ,
                ])
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
}
