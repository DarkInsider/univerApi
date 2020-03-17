<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePossibilityHasRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('possibility_has_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->string('scope');
            $table->bigInteger('role_id')->unsigned();
            $table->foreign('role_id')->references('id')->on('roles');
            $table->bigInteger('possibility_id')->unsigned();
            $table->foreign('possibility_id')->references('id')->on('possibilities');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('possibility_has_roles');
    }
}
