<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });
        DB::table('roles')->insert([
            ['id' => 1, 'title' => 'SU', 'hidden' => true],
            ['id' => 2, 'title' => 'AF', 'hidden' => false],
            ['id' => 3, 'title' => 'AK', 'hidden' => false],
            ['id' => 4, 'title' => 'Student', 'hidden' => false],
            ['id' => 5, 'title' => 'VU', 'hidden' => false],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}
