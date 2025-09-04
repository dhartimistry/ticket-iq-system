<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Services\TicketClassifier;

class TicketController extends Controller
{
    /**
     * Create a new ticket and queue it for AI classification.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $ticket = Ticket::create([
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'status' => 'open',
        ]);
        
        // Queue for AI classification
        \App\Jobs\ClassifyTicket::dispatch($ticket->id);
        
        return response()->json($ticket, 201);
    }

    /**
     * List tickets with optional filtering and search.
     * Supports filtering by status and category, and full-text search.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Ticket::query();

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%$search%")
                    ->orWhere('body', 'like', "%$search%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Apply category filter
        if ($request->filled('category')) {
            $category = $request->input('category');
            if ($category === 'Uncategorized') {
                $query->where(function($q) {
                    $q->whereNull('category')
                        ->orWhere('category', '');
                });
            } else {
                $query->where('category', $category);
            }
        }

        $tickets = $query->orderByDesc('created_at')
                        ->paginate(10);

        return response()->json($tickets);
    }

    /**
     * Get a specific ticket by ID.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function show(string $id)
    {
        $ticket = Ticket::findOrFail($id);
        return response()->json($ticket);
    }

    /**
     * Update ticket properties.
     * Allows updating status, category, and internal notes.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(Request $request, string $id)
    {
        $ticket = Ticket::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'sometimes|in:open,pending,closed',
            'category' => 'sometimes|string|max:255',
            'note' => 'sometimes|nullable|string',
        ]);

        $ticket->fill($validated)->save();
        
        return response()->json($ticket);
    }

    /**
     * Manually trigger AI classification for a ticket.
     * Updates category, explanation, and confidence score.
     *
     * @param string $id
     * @param TicketClassifier $classifier
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function classify(string $id, TicketClassifier $classifier)
    {
        $ticket = Ticket::findOrFail($id);
        $result = $classifier->classify($ticket->subject, $ticket->body);
        
        $ticket->fill([
            'category' => $result['category'],
            'explanation' => $result['explanation'],
            'confidence' => $result['confidence'],
        ])->save();

        return response()->json($ticket);
    }


    // GET /stats
    public function stats()
    {
        $statusCounts = Ticket::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
        
        // Handle null/empty categories properly
        $rawCategoryCounts = Ticket::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')->get();
        
        $categoryCounts = [];
        foreach ($rawCategoryCounts as $row) {
            $category = $row->category;
            if (empty($category)) {
                $category = 'Uncategorized';
            }
            $categoryCounts[$category] = $row->count;
        }
        
        $total = Ticket::count();
        return response()->json([
            'status' => $statusCounts,
            'category' => $categoryCounts,
            'total' => $total,
        ]);
    }
}
