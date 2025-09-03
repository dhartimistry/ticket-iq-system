<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClassifyTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $ticketId;

    public function __construct(string $ticketId)
    {
        $this->ticketId = $ticketId;
    }

    public function handle(TicketClassifier $classifier): void
    {
        $ticket = Ticket::find($this->ticketId);
        if (!$ticket) return;
        $result = $classifier->classify($ticket->subject, $ticket->body);
        // If category was manually set, keep it, but update explanation/confidence
        if ($ticket->isDirty('category')) {
            $ticket->explanation = $result['explanation'];
            $ticket->confidence = $result['confidence'];
        } else {
            $ticket->category = $result['category'];
            $ticket->explanation = $result['explanation'];
            $ticket->confidence = $result['confidence'];
        }
        $ticket->save();
    }
}
