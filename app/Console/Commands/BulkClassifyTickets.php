<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Jobs\ClassifyTicket;

class BulkClassifyTickets extends Command
{
    protected $signature = 'tickets:bulk-classify 
                            {--delay=1 : Delay in seconds between jobs for rate limiting}
                            {--batch-size=10 : Number of tickets to process in each batch}';
    protected $description = 'Dispatch AI classification jobs for all tickets with rate limiting';

    public function handle()
    {
        $delay = (int) $this->option('delay');
        $batchSize = (int) $this->option('batch-size');
        
        $tickets = Ticket::all();
        $totalTickets = $tickets->count();
        
        if ($totalTickets === 0) {
            $this->info('No tickets found to classify.');
            return;
        }
        
        $this->info("Found {$totalTickets} tickets to classify.");
        $this->info("Rate limiting: {$delay}s delay between jobs, {$batchSize} tickets per batch.");
        
        $processed = 0;
        $batches = $tickets->chunk($batchSize);
        
        foreach ($batches as $batch) {
            foreach ($batch as $ticket) {
                ClassifyTicket::dispatch($ticket->id);
                $processed++;
                
                if ($processed % 10 === 0) {
                    $this->info("Dispatched {$processed}/{$totalTickets} classification jobs...");
                }
            }
            
            // Rate limiting delay between batches
            if ($delay > 0 && $processed < $totalTickets) {
                $this->info("Rate limiting: waiting {$delay} seconds...");
                sleep($delay);
            }
        }
        
        $this->info("✅ Successfully dispatched {$processed} classification jobs.");
        $this->info("Jobs will be processed by the queue worker.");
    }
}
