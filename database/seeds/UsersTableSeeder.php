<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Workspace;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $workspace = Workspace::create([
            'title' => 'cbenjafield',
            'slug' => 'cbenjafield'
        ]);

        $user = User::create([
            'name' => 'Charlie Benjafield',
            'email' => 'me@cbenjafield.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $user->workspaces()->attach($workspace);
    }
}
