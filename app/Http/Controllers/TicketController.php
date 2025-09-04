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
    // POST /tickets
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
        return response()->json($ticket, 201);
    }

    // GET /tickets
    public function index(Request $request)
    {
        $query = Ticket::query();
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%$search%")
                  ->orWhere('body', 'like', "%$search%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('category')) {
            $category = $request->input('category');
            if ($category === 'Uncategorized') {
                $query->where(function($q) {
                    $q->whereNull('category')->orWhere('category', '');
                });
            } else {
                $query->where('category', $category);
            }
        }
        $tickets = $query->orderByDesc('created_at')->paginate(10);
        return response()->json($tickets);
    }

    // GET /tickets/{id}
    public function show(string $id)
    {
        $ticket = Ticket::findOrFail($id);
        return response()->json($ticket);
    }

    // PATCH /tickets/{id}
    public function update(Request $request, string $id)
    {
        $ticket = Ticket::findOrFail($id);
        $validated = $request->validate([
            'status' => 'sometimes|in:open,pending,closed',
            'category' => 'sometimes|string|max:255',
            'note' => 'sometimes|nullable|string',
        ]);
        $ticket->fill($validated);
        $ticket->save();
        return response()->json($ticket);
    }

    // POST /tickets/{id}/classify
    public function classify(string $id, TicketClassifier $classifier)
    {
        $ticket = Ticket::findOrFail($id);
        $result = $classifier->classify($ticket->subject, $ticket->body);
        if (!$ticket->category) {
            $ticket->category = $result['category'];
        }
        $ticket->explanation = $result['explanation'];
        $ticket->confidence = $result['confidence'];
        $ticket->save();

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
