<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subscriber;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $subscribers = [
            [
                'email' => 'user1@example.com',
                'phone' => '+79519876542',
            ],
            [
                'email' => 'user2@example.com',
                'phone' => '+79519876543',
            ],
            [
                'email' => 'fake@@fake.com',
                'phone' => '+79519876544',
            ],
        ];

        foreach ($subscribers as $subscriber) {
            Subscriber::query()->updateOrCreate(
                ['email' => $subscriber['email']],
                ['phone' => $subscriber['phone']]
            );
        }
    }
}
