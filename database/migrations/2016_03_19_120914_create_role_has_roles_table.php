<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoleHasRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_has_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('role_id')->unsigned();
            $table->foreign('role_id')->references('id')->on('roles');
            $table->bigInteger('role_id_has')->unsigned();
            $table->foreign('role_id_has')->references('id')->on('roles');
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });
        DB::table('role_has_roles')->insert([
            ['id' => 1, 'role_id' => 2, 'role_id_has' => 3],
            ['id' => 2, 'role_id' => 2, 'role_id_has' => 4],
            ['id' => 3, 'role_id' => 2, 'role_id_has' => 5],
            ['id' => 4, 'role_id' => 3, 'role_id_has' => 4],
            ['id' => 5, 'role_id' => 3, 'role_id_has' => 5]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_has_roles');
    }
}
