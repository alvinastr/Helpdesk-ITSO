<?php

// ======= CONTOH IMPLEMENTASI DELETE METHODS =======

// 1. Di Controller (contoh: AdminTicketController.php)
class AdminTicketController extends Controller
{
    /**
     * Soft delete ticket
     */
    public function destroy(Ticket $ticket)
    {
        // Authorization check
        $this->authorize('delete', $ticket);
        
        // Soft delete
        $ticket->delete();
        
        return back()->with('success', "Ticket {$ticket->ticket_number} telah dihapus.");
    }
    
    /**
     * Force delete ticket (permanent)
     */
    public function forceDestroy(Ticket $ticket)
    {
        // Extra authorization for permanent delete
        $this->authorize('forceDelete', $ticket);
        
        // Store ticket number before deletion
        $ticketNumber = $ticket->ticket_number;
        
        // Hard delete
        $ticket->forceDelete();
        
        return back()->with('success', "Ticket {$ticketNumber} telah dihapus permanen.");
    }
    
    /**
     * Restore soft deleted ticket
     */
    public function restore($id)
    {
        $ticket = Ticket::onlyTrashed()->findOrFail($id);
        $ticket->restore();
        
        return back()->with('success', "Ticket {$ticket->ticket_number} telah dipulihkan.");
    }
    
    /**
     * Bulk delete tickets
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'exists:tickets,id'
        ]);
        
        $count = Ticket::whereIn('id', $request->ticket_ids)->delete();
        
        return back()->with('success', "{$count} tiket telah dihapus.");
    }
}

// ======= ROUTE DEFINITIONS =======

// Di routes/web.php, tambahkan:
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // Existing routes...
    
    // Delete routes
    Route::delete('/tickets/{ticket}', [AdminTicketController::class, 'destroy'])->name('admin.tickets.destroy');
    Route::delete('/tickets/{ticket}/force', [AdminTicketController::class, 'forceDestroy'])->name('admin.tickets.force-destroy');
    Route::post('/tickets/{id}/restore', [AdminTicketController::class, 'restore'])->name('admin.tickets.restore');
    Route::post('/tickets/bulk-delete', [AdminTicketController::class, 'bulkDelete'])->name('admin.tickets.bulk-delete');
});

// ======= VIEW EXAMPLES =======

// Untuk single delete button di view:
?>
<form action="{{ route('admin.tickets.destroy', $ticket) }}" method="POST" 
      style="display: inline;" 
      onsubmit="return confirm('Yakin ingin menghapus tiket ini?')">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-danger btn-sm">
        <i class="fas fa-trash"></i> Hapus
    </button>
</form>

<!-- Force delete button (permanent) -->
<form action="{{ route('admin.tickets.force-destroy', $ticket) }}" method="POST" 
      style="display: inline;" 
      onsubmit="return confirm('PERHATIAN: Data akan dihapus PERMANEN! Yakin ingin melanjutkan?')">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-danger btn-sm">
        <i class="fas fa-times"></i> Hapus Permanen
    </button>
</form>

<!-- Restore button for soft deleted items -->
<form action="{{ route('admin.tickets.restore', $ticket->id) }}" method="POST" style="display: inline;">
    @csrf
    <button type="submit" class="btn btn-success btn-sm">
        <i class="fas fa-undo"></i> Pulihkan
    </button>
</form>

<?php
// ======= ELOQUENT QUERY EXAMPLES =======

// Query berbagai jenis data
class TicketService 
{
    public function getActiveTickets()
    {
        // Hanya data yang tidak dihapus
        return Ticket::all();
    }
    
    public function getDeletedTickets()
    {
        // Hanya data yang sudah di-soft delete
        return Ticket::onlyTrashed()->get();
    }
    
    public function getAllTicketsIncludingDeleted()
    {
        // Semua data termasuk yang dihapus
        return Ticket::withTrashed()->get();
    }
    
    public function cleanupOldTickets($days = 365)
    {
        $date = now()->subDays($days);
        
        // Soft delete old closed tickets
        $softDeleteCount = Ticket::where('status', 'closed')
            ->where('closed_at', '<', $date)
            ->delete();
            
        // Force delete very old tickets (already soft deleted)
        $hardDeleteCount = Ticket::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days * 2))
            ->forceDelete();
            
        return [
            'soft_deleted' => $softDeleteCount,
            'hard_deleted' => $hardDeleteCount
        ];
    }
}

// ======= POLICY EXAMPLES =======

// Di app/Policies/TicketPolicy.php
class TicketPolicy
{
    public function delete(User $user, Ticket $ticket)
    {
        // Only admin can delete tickets
        return $user->role === 'admin';
    }
    
    public function forceDelete(User $user, Ticket $ticket)
    {
        // Only super admin can permanently delete
        return $user->role === 'admin' && $user->email === 'admin@itso.com';
    }
    
    public function restore(User $user, Ticket $ticket)
    {
        // Admin can restore deleted tickets
        return $user->role === 'admin';
    }
}
?>