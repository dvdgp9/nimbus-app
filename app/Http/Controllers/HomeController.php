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
        $connectedCount = DB::table('google_tokens')
            ->where('user_id', $user?->id)
            ->count();
        $latest = DB::table('google_tokens')
            ->where('user_id', $user?->id)
            ->orderByDesc('updated_at')
            ->first();
        $account = $latest->account_email ?? null;
        $isConnected = $connectedCount > 0;
        
        // Calendar stats
        $enabledCalendars = $account
            ? DB::table('connected_calendars')
                ->where('user_id', $user?->id)
                ->where('account_email', $account)
                ->where('enabled', 1)
                ->count()
            : 0;

        $calendarIds = DB::table('connected_calendars')
            ->where('user_id', $user?->id)
            ->where('enabled', 1)
            ->pluck('calendar_id')
            ->all();
        
        // Upcoming appointments (next 7 days)
        $dashboardDaysAhead = 7;
        $upcomingAppointmentsQuery = Appointment::with('patient')
            ->when($calendarIds, function ($query) use ($calendarIds) {
                $query->whereIn('calendar_id', $calendarIds);
            })
            ->where('start_at', '>=', now())
            ->where('start_at', '<', now()->addDays($dashboardDaysAhead));
        
        $upcomingAppointments = (clone $upcomingAppointmentsQuery)
            ->orderBy('start_at')
            ->limit(10)
            ->get();
        
        $upcomingCount = (clone $upcomingAppointmentsQuery)
            ->count();

        $upcomingWithReminderCount = (clone $upcomingAppointmentsQuery)
            ->whereNotNull('reminder_sent_at')
            ->count();

        $upcomingPendingReminderCount = (clone $upcomingAppointmentsQuery)
            ->whereNull('reminder_sent_at')
            ->whereNotNull('patient_id')
            ->whereNotIn('nimbus_status', ['cancelled', 'cancelled_acknowledged'])
            ->count();

        $upcomingMissingPatientCount = (clone $upcomingAppointmentsQuery)
            ->whereNull('patient_id')
            ->whereNotIn('nimbus_status', ['cancelled', 'cancelled_acknowledged'])
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
        $lastSyncedAt = DB::table('appointments')
            ->when($calendarIds, function ($query) use ($calendarIds) {
                $query->whereIn('calendar_id', $calendarIds);
            })
            ->max('last_synced_at');
        
        // Patients count
        $patientsCount = $user?->patients()->count() ?? 0;

        return view('home', compact(
            'isConnected',
            'account',
            'enabledCalendars',
            'dashboardDaysAhead',
            'upcomingAppointments',
            'upcomingCount',
            'upcomingWithReminderCount',
            'upcomingPendingReminderCount',
            'upcomingMissingPatientCount',
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
