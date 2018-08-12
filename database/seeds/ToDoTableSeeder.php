<?php

use Illuminate\Database\Seeder;

class ToDoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('todos')->insert([
            'name' => str_random(10),
            'description' => str_random(10)
        ]);
    }
}
