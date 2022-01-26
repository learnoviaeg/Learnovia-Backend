<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Dictionary;

class ChangeDictionariesValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Dictionary::where('value','=TRUE()')->update(['value' => 'True']);
        Dictionary::where('value','=TRUE()')->update(['value' => 'True']);
        Dictionary::where('key','=FALSE()')->update(['key' => 'False']);
        Dictionary::where('key','=FALSE()')->update(['key' => 'False']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
