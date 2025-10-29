<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $connectedCount = DB::table('google_tokens')->count();
        $latest = DB::table('google_tokens')->orderByDesc('updated_at')->first();
        $account = $latest->account_email ?? null;
        $enabledCalendars = $account
            ? DB::table('connected_calendars')->where('account_email', $account)->where('enabled', 1)->count()
            : 0;
        $upcomingAppointments = DB::table('appointments')
            ->where('start_at', '>=', now())
            ->where('start_at', '<', now()->addHours(48))
            ->count();
        $lastSyncedAt = DB::table('appointments')->max('last_synced_at');

        return view('home', compact('connectedCount','account','enabledCalendars','upcomingAppointments','lastSyncedAt'));
    }
}
