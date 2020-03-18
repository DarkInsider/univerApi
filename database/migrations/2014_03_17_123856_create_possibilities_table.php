<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePossibilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('possibilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->timestamps();
        });
        DB::table('possibilities')->insert([
            ['title' => 'Отримати факультет' ],
            ['title' => 'Створити факультет' ],
            ['title' => 'Редагувати факультет' ],
            ['title' => 'Видалити факультет' ],

            ['title' => 'Отримати кафедру' ],
            ['title' => 'Створити кафедру' ],
            ['title' => 'Редагувати кафедру' ],
            ['title' => 'Видалити кафедру' ],

            ['title' => 'Отримати роль' ],
            ['title' => 'Створити роль' ],
            ['title' => 'Редагувати роль' ],
            ['title' => 'Видалити роль' ],

            ['title' => 'Отримати користувача' ],
            ['title' => 'Створити користувача' ],
            ['title' => 'Редагувати користувача' ],
            ['title' => 'Видалити користувача' ],

            ['title' => 'Отримати викладача' ],
            ['title' => 'Створити викладача' ],
            ['title' => 'Редагувати викладача' ],
            ['title' => 'Видалити викладача' ],

            ['title' => 'Отримати студента' ],
            ['title' => 'Створити студента' ],
            ['title' => 'Редагувати студента' ],
            ['title' => 'Видалити студента' ],

            ['title' => 'Отримати групу' ],
            ['title' => 'Створити групу' ],
            ['title' => 'Редагувати групу' ],
            ['title' => 'Видалити групу' ],

            ['title' => 'Отримати план' ],
            ['title' => 'Створити план' ],
            ['title' => 'Редагувати план' ],
            ['title' => 'Видалити план' ],

            ['title' => 'Отримати предмет' ],
            ['title' => 'Створити предмет' ],
            ['title' => 'Редагувати предмет' ],
            ['title' => 'Видалити предмет' ],

            ['title' => 'Отримати вибір' ],
            ['title' => 'Створити вибір' ],
            ['title' => 'Редагувати вибір' ],
            ['title' => 'Видалити вибір' ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('possibilities');
    }
}
