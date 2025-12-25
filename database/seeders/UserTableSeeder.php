<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = array(
            [
                'name' => 'OFEE',
                'email' => 'ofeehotel@mail.com',
                'password' => bcrypt('password@ofeehotel'),
                'foto' => '/img/user.jpg',
                'level' => 1
            ],
            [
                'name' => 'CodeAstro',
                'email' => 'astro@mail.com',
                'password' => bcrypt('codeastro.com'),
                'foto' => '/img/user.jpg',
                'level' => 2
            ]
        );

        array_map(function (array $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }, $users);
    }
}
