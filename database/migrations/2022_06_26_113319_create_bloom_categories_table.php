<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\BloomCategory;

class CreateBloomCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bloom_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('default')->default(0);
            $table->integer('current')->default(1);
            $table->timestamps();
        });

        //create default Bllom Categories
        $categories=[
            [
                'name' => 'Remember',
                'default' => 1,
                'current' => 1,
            ],
            [
                'name' => 'Understand',
                'default' => 1,
                'current' => 1,
            ],
            [
                'name' => 'Apply',
                'default' => 1,
                'current' => 1,
            ],
            [
                'name' => 'Analyze',
                'default' => 1,
                'current' => 1,
            ],
            [
                'name' => 'Evaluate',
                'default' => 1,
                'current' => 1,
            ],
            [
                'name' => 'Create',
                'default' => 1,
                'current' => 1,
            ]
        ];

        BloomCategory::insert($categories);

        Artisan::call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true 
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bloom_categories');
    }
}
