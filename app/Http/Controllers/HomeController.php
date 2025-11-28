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
                ->whereIn('status', ['open', 'in_progress', 'pending_keluhan'])->count(),
            'closed_tickets' => Ticket::where('user_id', $user->id)
                ->where('status', 'closed')->count(),
            'pending_keluhan' => Ticket::where('user_id', $user->id)
                ->where('status', 'pending_keluhan')->count(),
        ];

        $recent_tickets = Ticket::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('dashboard', compact('stats', 'recent_tickets'));
    }
}