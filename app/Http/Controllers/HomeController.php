<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        
        $stats = [
            'total_tickets' => Ticket::where('user_id', $user->id)->count(),
            'open_tickets' => Ticket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'in_progress', 'pending_review'])->count(),
            'closed_tickets' => Ticket::where('user_id', $user->id)
                ->where('status', 'closed')->count(),
            'pending_review' => Ticket::where('user_id', $user->id)
                ->where('status', 'pending_review')->count(),
        ];

        $recent_tickets = Ticket::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'recent_tickets'));
    }
}