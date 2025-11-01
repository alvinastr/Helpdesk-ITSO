<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Services\KpiCalculationService;
use Carbon\Carbon;

class KpiTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpi:test 
                            {action=summary : Action to perform (summary|calculate|validate|analyze)}
                            {--ticket= : Specific ticket ID to test}
                            {--all : Process all tickets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and validate KPI calculations';

    protected $kpiService;

    /**
     * Create a new command instance.
     */
    public function __construct(KpiCalculationService $kpiService)
    {
        parent::__construct();
        $this->kpiService = $kpiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        $this->info("🎯 KPI Test Command - Action: " . strtoupper($action));
        $this->line(str_repeat('=', 60));

        switch ($action) {
            case 'summary':
                $this->showSummary();
                break;
            
            case 'calculate':
                $this->recalculateKpi();
                break;
            
            case 'validate':
                $this->validateKpi();
                break;
            
            case 'analyze':
                $this->analyzeKpi();
                break;
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: summary, calculate, validate, analyze");
                return 1;
        }

        return 0;
    }

    /**
     * Show KPI Summary
     */
    private function showSummary(): void
    {
        $this->info("\n📊 KPI SUMMARY\n");

        // Get overall summary
        $summary = $this->kpiService->getKpiSummary([]);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Tickets', $summary['total_tickets']],
                ['Tickets with Response', $summary['tickets_with_response'] . ' (' . $summary['response_rate'] . '%)'],
                ['Tickets Resolved', $summary['tickets_resolved']],
                ['Avg Response Time', $summary['avg_response_time_formatted'] . ' (' . $summary['avg_response_time_minutes'] . ' min)'],
                ['Avg Resolution Time', $summary['avg_resolution_time_formatted'] . ' (' . $summary['avg_resolution_time_minutes'] . ' min)'],
                ['Avg Creation Delay', $summary['avg_creation_delay_formatted'] ?? 'N/A'],
            ]
        );

        // SLA Compliance
        $this->info("\n🎯 SLA COMPLIANCE\n");
        $this->table(
            ['Metric', 'Target', 'Compliance', 'Status'],
            [
                [
                    'Response Time',
                    '≤ 30 minutes',
                    $summary['sla_response_compliance'] . '%',
                    $summary['sla_response_compliance'] >= 80 ? '✅ PASS' : '❌ FAIL'
                ],
                [
                    'Resolution Time',
                    '≤ 48 hours',
                    $summary['sla_resolution_compliance'] . '%',
                    $summary['sla_resolution_compliance'] >= 80 ? '✅ PASS' : '❌ FAIL'
                ],
            ]
        );

        // Recent tickets with issues
        $slowTickets = Ticket::where(function ($q) {
            $q->where(function ($query) {
                $query->whereNotNull('response_time_minutes')
                      ->where('response_time_minutes', '>', 30);
            })->orWhere(function ($query) {
                $query->whereNotNull('resolution_time_minutes')
                      ->where('resolution_time_minutes', '>', 2880);
            });
        })
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

        if ($slowTickets->isNotEmpty()) {
            $this->warn("\n⚠️  TICKETS WITH SLA ISSUES (Top 5)\n");
            
            $data = $slowTickets->map(function ($ticket) {
                $issues = [];
                if ($ticket->response_time_minutes && $ticket->response_time_minutes > 30) {
                    $issues[] = "Response: {$ticket->getResponseTimeFormatted()}";
                }
                if ($ticket->resolution_time_minutes && $ticket->resolution_time_minutes > 2880) {
                    $issues[] = "Resolution: {$ticket->getResolutionTimeFormatted()}";
                }
                
                return [
                    $ticket->ticket_number,
                    substr($ticket->subject, 0, 30) . '...',
                    $ticket->priority,
                    implode(' | ', $issues),
                ];
            })->toArray();

            $this->table(
                ['Ticket', 'Subject', 'Priority', 'Issues'],
                $data
            );
        } else {
            $this->info("\n✅ No tickets with SLA issues!");
        }
    }

    /**
     * Recalculate KPI for tickets
     */
    private function recalculateKpi(): void
    {
        $ticketId = $this->option('ticket');
        $all = $this->option('all');

        if ($ticketId) {
            $this->recalculateTicket($ticketId);
        } elseif ($all) {
            $this->recalculateAllTickets();
        } else {
            $this->error("Please specify --ticket=ID or --all");
            return;
        }
    }

    /**
     * Recalculate single ticket
     */
    private function recalculateTicket(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            $this->error("Ticket #{$ticketId} not found!");
            return;
        }

        $this->info("\n🔄 Recalculating KPI for Ticket #{$ticket->ticket_number}\n");

        // Show before
        $this->line("BEFORE:");
        $this->showTicketKpi($ticket);

        // Recalculate
        if ($ticket->email_received_at) {
            $ticket->calculateTicketCreationDelay();
        }
        
        if ($ticket->first_response_at) {
            $ticket->calculateResponseTime();
        }
        
        if ($ticket->resolved_at) {
            $ticket->calculateResolutionTime();
        }
        
        $ticket->save();

        // Show after
        $this->line("\nAFTER:");
        $ticket->refresh();
        $this->showTicketKpi($ticket);

        $this->info("\n✅ Recalculation complete!");
    }

    /**
     * Recalculate all tickets
     */
    private function recalculateAllTickets(): void
    {
        $tickets = Ticket::all();
        $bar = $this->output->createProgressBar($tickets->count());
        $bar->start();

        $updated = 0;

        foreach ($tickets as $ticket) {
            $changed = false;

            if ($ticket->email_received_at) {
                $old = $ticket->ticket_creation_delay_minutes;
                $ticket->calculateTicketCreationDelay();
                if ($old !== $ticket->ticket_creation_delay_minutes) {
                    $changed = true;
                }
            }
            
            if ($ticket->first_response_at) {
                $old = $ticket->response_time_minutes;
                $ticket->calculateResponseTime();
                if ($old !== $ticket->response_time_minutes) {
                    $changed = true;
                }
            }
            
            if ($ticket->resolved_at) {
                $old = $ticket->resolution_time_minutes;
                $ticket->calculateResolutionTime();
                if ($old !== $ticket->resolution_time_minutes) {
                    $changed = true;
                }
            }

            if ($changed) {
                $ticket->save();
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line("\n");
        $this->info("✅ Processed {$tickets->count()} tickets, updated {$updated} tickets.");
    }

    /**
     * Validate KPI calculations
     */
    private function validateKpi(): void
    {
        $this->info("\n🔍 VALIDATING KPI CALCULATIONS\n");

        $issues = [];

        // Check tickets with response time
        $tickets = Ticket::whereNotNull('first_response_at')->get();

        foreach ($tickets as $ticket) {
            // Validate response time
            if ($ticket->first_response_at && !$ticket->response_time_minutes) {
                $issues[] = [
                    $ticket->ticket_number,
                    'Missing response_time_minutes',
                    'Has first_response_at but no calculated time',
                ];
            }

            // Validate resolution time
            if ($ticket->resolved_at && !$ticket->resolution_time_minutes) {
                $issues[] = [
                    $ticket->ticket_number,
                    'Missing resolution_time_minutes',
                    'Has resolved_at but no calculated time',
                ];
            }

            // Validate creation delay
            if ($ticket->email_received_at && !$ticket->ticket_creation_delay_minutes) {
                $issues[] = [
                    $ticket->ticket_number,
                    'Missing ticket_creation_delay_minutes',
                    'Has email_received_at but no calculated delay',
                ];
            }

            // Validate logic: response should be before resolution
            if ($ticket->first_response_at && $ticket->resolved_at) {
                if ($ticket->first_response_at > $ticket->resolved_at) {
                    $issues[] = [
                        $ticket->ticket_number,
                        'Invalid timestamps',
                        'first_response_at is after resolved_at',
                    ];
                }
            }

            // Validate: email_received should be before created_at
            if ($ticket->email_received_at && $ticket->created_at) {
                if ($ticket->email_received_at > $ticket->created_at) {
                    $issues[] = [
                        $ticket->ticket_number,
                        'Invalid timestamps',
                        'email_received_at is after created_at',
                    ];
                }
            }
        }

        if (empty($issues)) {
            $this->info("✅ All KPI calculations are valid!");
        } else {
            $this->warn(count($issues) . " validation issues found:\n");
            $this->table(
                ['Ticket', 'Issue', 'Details'],
                $issues
            );

            if ($this->confirm('Do you want to fix these issues?')) {
                $this->recalculateAllTickets();
            }
        }
    }

    /**
     * Analyze KPI trends
     */
    private function analyzeKpi(): void
    {
        $this->info("\n📈 KPI ANALYSIS\n");

        // By Priority
        $this->line("BY PRIORITY:");
        $byPriority = $this->kpiService->getKpiByPriority([]);
        
        $this->table(
            ['Priority', 'Total', 'Avg Response', 'Avg Resolution', 'SLA Status'],
            collect($byPriority)->map(function ($item) {
                $responseOk = $item['avg_response_time'] <= 30;
                $resolutionOk = $item['avg_resolution_time'] <= 2880;
                
                return [
                    ucfirst($item['priority']),
                    $item['total_tickets'],
                    $item['avg_response_time_formatted'] . ($responseOk ? ' ✅' : ' ❌'),
                    $item['avg_resolution_time_formatted'] . ($resolutionOk ? ' ✅' : ' ❌'),
                    ($responseOk && $resolutionOk) ? '✅ Good' : '⚠️  Attention Needed',
                ];
            })->toArray()
        );

        // By Category
        $this->line("\nBY CATEGORY:");
        $byCategory = $this->kpiService->getKpiByCategory([]);
        
        $this->table(
            ['Category', 'Total', 'Avg Response', 'Avg Resolution'],
            collect($byCategory)->map(function ($item) {
                return [
                    $item['category'],
                    $item['total_tickets'],
                    $item['avg_response_time_formatted'],
                    $item['avg_resolution_time_formatted'],
                ];
            })->toArray()
        );

        // Recommendations
        $this->info("\n💡 RECOMMENDATIONS:\n");
        
        $critical = collect($byPriority)->firstWhere('priority', 'critical');
        if ($critical && $critical['avg_response_time'] > 30) {
            $this->warn("⚠️  Critical priority tickets have slow response time ({$critical['avg_response_time_formatted']})");
            $this->line("   → Consider implementing automated escalation for critical tickets\n");
        }

        $highDelay = Ticket::whereNotNull('ticket_creation_delay_minutes')
            ->where('ticket_creation_delay_minutes', '>', 60)
            ->count();
        
        if ($highDelay > 0) {
            $this->warn("⚠️  {$highDelay} tickets have creation delay > 1 hour");
            $this->line("   → Train admin staff to create tickets immediately upon receiving emails\n");
        }

        $noResponse = Ticket::whereNull('first_response_at')
            ->where('created_at', '<', Carbon::now()->subMinutes(30))
            ->count();
        
        if ($noResponse > 0) {
            $this->warn("⚠️  {$noResponse} tickets older than 30 minutes have no response yet");
            $this->line("   → Review ticket assignment and notification system\n");
        }
    }

    /**
     * Show ticket KPI details
     */
    private function showTicketKpi(Ticket $ticket): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Ticket Number', $ticket->ticket_number],
                ['Status', $ticket->status],
                ['Priority', $ticket->priority],
                ['Channel', $ticket->channel],
                ['---', '---'],
                ['Email Received At', $ticket->email_received_at ?? 'N/A'],
                ['Created At', $ticket->created_at],
                ['First Response At', $ticket->first_response_at ?? 'N/A'],
                ['Resolved At', $ticket->resolved_at ?? 'N/A'],
                ['---', '---'],
                ['Creation Delay', $ticket->ticket_creation_delay_minutes ? $ticket->ticket_creation_delay_minutes . ' min' : 'N/A'],
                ['Response Time', $ticket->response_time_minutes ? $ticket->response_time_minutes . ' min (' . $ticket->getResponseTimeFormatted() . ')' : 'N/A'],
                ['Resolution Time', $ticket->resolution_time_minutes ? $ticket->resolution_time_minutes . ' min (' . $ticket->getResolutionTimeFormatted() . ')' : 'N/A'],
                ['---', '---'],
                ['Response SLA', $ticket->response_time_minutes ? ($ticket->isResponseTimeWithinTarget() ? '✅ Met' : '❌ Missed') : 'N/A'],
                ['Resolution SLA', $ticket->resolution_time_minutes ? ($ticket->isResolutionTimeWithinTarget() ? '✅ Met' : '❌ Missed') : 'N/A'],
            ]
        );
    }
}
