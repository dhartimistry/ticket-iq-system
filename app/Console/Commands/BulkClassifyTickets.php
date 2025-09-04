<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Jobs\ClassifyTicket;

class BulkClassifyTickets extends Command
{
    protected $signature = 'tickets:bulk-classify';
    protected $description = 'Dispatch AI classification jobs for all tickets';

    public function handle()
    {
        $tickets = Ticket::all();
        foreach ($tickets as $ticket) {
            ClassifyTicket::dispatch($ticket);
        }
        $this->info('Dispatched classify jobs for all tickets.');
    }
}
