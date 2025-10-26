<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\User;

class CreateSampleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sample:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample tickets for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('role', 'user')->first();
        
        if (!$user) {
            $this->error('No user found. Please run db:seed first.');
            return;
        }

        // Create sample tickets
        Ticket::factory(5)->create([
            'status' => 'pending_review',
            'user_id' => $user->id,
        ]);

        Ticket::factory(3)->create([
            'status' => 'open', 
            'user_id' => $user->id,
        ]);

        Ticket::factory(2)->create([
            'status' => 'resolved',
            'user_id' => $user->id,
        ]);

        $this->info('Sample tickets created successfully!');
    }
}
