<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('type', ['N', 'V']);
            $table->string('subject_name')->nullable();
            $table->integer('semester');
            $table->enum('zalic_or_examen', ['Z', 'E'])->nullable();
            $table->integer('z_or_e_number')->nullable();
            $table->integer('cours_projects')->nullable();
            $table->integer('cours_work')->nullable();
            $table->integer('leccii')->nullable();
            $table->integer('laborat')->nullable();
            $table->integer('practik')->nullable();
            $table->integer('samostiyna_robta')->nullable();
            $table->integer('weeks_in_semester');
            $table->integer('par_per_week');
            $table->float('credits_ECTS');
            $table->bigInteger('plan_id')->unsigned();
            $table->foreign('plan_id')->references('id')->on('plans');
            $table->longText('subject_description')->nullable();
            $table->integer('difficult')->default(1);
            $table->boolean('hidden')->default(false);
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
        Schema::dropIfExists('notes');
    }
}
