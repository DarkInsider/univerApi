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
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });
        DB::table('possibility_has_roles')->insert([
            ['id' => 1, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 1],
            ['id' => 3, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 3],

            ['id' => 4, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 5],
            ['id' => 5, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 6],
            ['id' => 6, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 7],
            ['id' => 7, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 8],

            ['id' => 69, 'type' => 'role', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 9],

            ['id' => 8, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 13],
            ['id' => 9, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 14],
            ['id' => 10, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 15],
            ['id' => 11, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 16],

            ['id' => 12, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 17],
            ['id' => 13, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 18],
            ['id' => 14, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 19],
            ['id' => 15, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 20],

            ['id' => 16, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 21],
            ['id' => 17, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 22],
            ['id' => 18, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 23],
            ['id' => 19, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 24],

            ['id' => 20, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 25],
            ['id' => 21, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 26],
            ['id' => 22, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 27],
            ['id' => 23, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 28],

            ['id' => 24, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 29],
            ['id' => 25, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 30],
            ['id' => 26, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 31],
            ['id' => 27, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 32],

            ['id' => 28, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 33],
            ['id' => 29, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 34],
            ['id' => 30, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 35],
            ['id' => 31, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 36],

            ['id' => 61, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 41],
            ['id' => 62, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 42],
            ['id' => 63, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 43],
            ['id' => 64, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 44],


            ['id' => 32, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 1],

            ['id' => 33, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 5],
            ['id' => 34, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 7],

            ['id' => 35, 'type' => 'role', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 9],

            ['id' => 36, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 13],
            ['id' => 37, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 14],
            ['id' => 38, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 15],
            ['id' => 39, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 16],

            ['id' => 40, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 17],
            ['id' => 41, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 18],
            ['id' => 42, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 19],
            ['id' => 43, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 20],

            ['id' => 45, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 21],
            ['id' => 46, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 22],
            ['id' => 47, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 23],
            ['id' => 48, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 24],

            ['id' => 49, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 25],
            ['id' => 50, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 26],
            ['id' => 51, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 27],
            ['id' => 52, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 28],

            ['id' => 53, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 29],
            ['id' => 54, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 30],
            ['id' => 55, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 31],
            ['id' => 56, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 32],

            ['id' => 57, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 33],
            ['id' => 58, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 34],
            ['id' => 59, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 35],
            ['id' => 60, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 36],

            ['id' => 65, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 41],
            ['id' => 66, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 42],
            ['id' => 67, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 43],
            ['id' => 68, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 44],

            ['id' => 75, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 37],
            ['id' => 76, 'type' => 'faculty', 'scope' => 'own', 'role_id' => 2, 'possibility_id' => 38],


            ['id' => 79, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 37],
            ['id' => 80, 'type' => 'department', 'scope' => 'own', 'role_id' => 3, 'possibility_id' => 38],

        ]);
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
