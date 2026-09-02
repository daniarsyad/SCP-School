<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $config = SystemConfiguration::current();

        // Alert: Guru yang belum isi jurnal (>30 menit setelah jam selesai)
        $today = Carbon::now()->locale('id')->isoFormat('dddd'); // Senin, Selasa, dst
        $now = Carbon::now();

        $lateJournals = Schedule::with(['teacher', 'class', 'room'])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $today)
            ->whereTime('end_time', '<', $now->copy()->subMinutes(30)->format('H:i:s'))
            ->whereDoesntHave('journals', function ($q) {
                $q->whereDate('date', Carbon::today());
            })
            ->get();

        return view('waka.dashboard', compact('config', 'lateJournals'));
    }
}