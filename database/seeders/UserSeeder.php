<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'test@test.de',
            'phone_number' => null,
            'password' => Hash::make('TestPass1234!$'),
            'position' => 'Tester',
            'business' => 'TestBusiness',
            'description' => null,
            'toggle_hints' => true
        ]);

        $user->assignRole('admin');
    }
}
