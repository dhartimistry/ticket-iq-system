<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        // Force English locale for faker
        app()->setLocale('en');
        fake()->locale('en_US');
        
        // Create 35+ tickets using Factory + Faker (≥25 as required)
        Ticket::factory()->count(35)->create();
    }
}
