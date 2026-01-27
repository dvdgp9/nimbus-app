<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Communication;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        // Connection status
        $connectedCount = DB::table('google_tokens')->count();
        $latest = DB::table('google_tokens')->orderByDesc('updated_at')->first();
        $account = $latest->account_email ?? null;
        $isConnected = $connectedCount > 0;
        
        // Calendar stats
        $enabledCalendars = $account
            ? DB::table('connected_calendars')->where('account_email', $account)->where('enabled', 1)->count()
            : 0;
        
        // Upcoming appointments (next 48h)
        $upcomingAppointments = Appointment::with('patient')
            ->where('start_at', '>=', now())
            ->where('start_at', '<', now()->addHours(48))
            ->orderBy('start_at')
            ->limit(5)
            ->get();
        
        $upcomingCount = Appointment::where('start_at', '>=', now())
            ->where('start_at', '<', now()->addHours(48))
            ->count();
        
        // Today's stats
        $remindersSentToday = Communication::where('channel', '!=', 'system')
            ->where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();
        
        // This week's confirmations
        $confirmedThisWeek = Appointment::where('nimbus_status', 'confirmed')
            ->whereBetween('confirmed_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        
        // User's templates
        $emailTemplate = $user?->messageTemplates()->forChannel('email')->default()->first();
        $smsTemplate = $user?->messageTemplates()->forChannel('sms')->default()->first();
        $hasTemplates = $emailTemplate || $smsTemplate;
        
        // Last sync
        $lastSyncedAt = DB::table('appointments')->max('last_synced_at');
        
        // Patients count
        $patientsCount = $user?->patients()->count() ?? 0;

        return view('home', compact(
            'isConnected',
            'account',
            'enabledCalendars',
            'upcomingAppointments',
            'upcomingCount',
            'remindersSentToday',
            'confirmedThisWeek',
            'emailTemplate',
            'smsTemplate',
            'hasTemplates',
            'lastSyncedAt',
            'patientsCount'
        ));
    }
}
