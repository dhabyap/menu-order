<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EventLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::latest('created_at')->limit(50)->get();

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * Filterable system log: the structured audit trail recorded by
     * {@see \App\Services\EventLogger}.
     */
    public function systemLog(Request $request)
    {
        $query = EventLog::latest('created_at')->limit(100);

        if ($event = $request->input('event')) {
            $query->where('event', 'like', '%'.$event.'%');
        }
        if ($level = $request->input('level')) {
            $query->where('level', $level);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return response()->json(['events' => $query->get()]);
    }
}
