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

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(string $ticketId)
    {
        $this->ticketId = $ticketId;
    }

    /**
     * Execute the job.
     * 
     * This job classifies a ticket using AI or keyword-based classification.
     * If the ticket was manually categorized, it preserves the category
     * but updates the explanation and confidence.
     */
    public function handle(TicketClassifier $classifier): void
    {
        $ticket = Ticket::find($this->ticketId);
        if (!$ticket) {
            // Ticket was deleted, no need to retry
            $this->delete();
            return;
        }
        
        // Preserve manual categorization
        $hasManualCategory = !empty($ticket->category) && empty($ticket->explanation);
        
        try {
            $result = $classifier->classify($ticket->subject, $ticket->body);
            
            $ticket->fill([
                'category' => $hasManualCategory ? $ticket->category : $result['category'],
                'explanation' => $result['explanation'],
                'confidence' => $result['confidence']
            ])->save();
        } catch (\Exception $e) {
            // Log error and allow retry
            \Illuminate\Support\Facades\Log::error(
                'Failed to classify ticket: ' . $e->getMessage(),
                ['ticket_id' => $this->ticketId]
            );
            throw $e;
        }
    }
}
