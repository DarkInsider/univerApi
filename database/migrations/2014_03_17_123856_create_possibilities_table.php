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
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });
        DB::table('possibilities')->insert([
            ['id' => 1, 'title' => 'Отримати факультет' ],
            ['id' => 2, 'title' => 'Створити факультет' ],
            ['id' => 3, 'title' => 'Редагувати факультет' ],
            ['id' => 4, 'title' => 'Видалити факультет' ],

            ['id' => 5, 'title' => 'Отримати кафедру' ],
            ['id' => 6, 'title' => 'Створити кафедру' ],
            ['id' => 7, 'title' => 'Редагувати кафедру' ],
            ['id' => 8, 'title' => 'Видалити кафедру' ],

            ['id' => 9, 'title' => 'Отримати роль' ],
            ['id' => 10, 'title' => 'Створити роль' ],
            ['id' => 11, 'title' => 'Редагувати роль' ],
            ['id' => 12, 'title' => 'Видалити роль' ],

            ['id' => 13, 'title' => 'Отримати користувача' ],
            ['id' => 14, 'title' => 'Створити користувача' ],
            ['id' => 15, 'title' => 'Редагувати користувача' ],
            ['id' => 16, 'title' => 'Видалити користувача' ],

            ['id' => 17, 'title' => 'Отримати викладача' ],
            ['id' => 18, 'title' => 'Створити викладача' ],
            ['id' => 19, 'title' => 'Редагувати викладача' ],
            ['id' => 20, 'title' => 'Видалити викладача' ],

            ['id' => 21, 'title' => 'Отримати студента' ],
            ['id' => 22, 'title' => 'Створити студента' ],
            ['id' => 23, 'title' => 'Редагувати студента' ],
            ['id' => 24, 'title' => 'Видалити студента' ],

            ['id' => 25, 'title' => 'Отримати групу' ],
            ['id' => 26, 'title' => 'Створити групу' ],
            ['id' => 27, 'title' => 'Редагувати групу' ],
            ['id' => 28, 'title' => 'Видалити групу' ],

            ['id' => 29, 'title' => 'Отримати план' ],
            ['id' => 30, 'title' => 'Створити план' ],
            ['id' => 31, 'title' => 'Редагувати план' ],
            ['id' => 32, 'title' => 'Видалити план' ],

            ['id' => 33, 'title' => 'Отримати предмет' ],
            ['id' => 34, 'title' => 'Створити предмет' ],
            ['id' => 35, 'title' => 'Редагувати предмет' ],
            ['id' => 36, 'title' => 'Видалити предмет' ],

            ['id' => 37, 'title' => 'Отримати вибір' ],
            ['id' => 38, 'title' => 'Створити вибір' ],
            ['id' => 39, 'title' => 'Редагувати вибір' ],
            ['id' => 40, 'title' => 'Видалити вибір' ],

            ['id' => 41, 'title' => 'Отримати запис' ],
            ['id' => 42, 'title' => 'Створити запис' ],
            ['id' => 43, 'title' => 'Редагувати запис' ],
            ['id' => 44, 'title' => 'Видалити запис' ],
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
