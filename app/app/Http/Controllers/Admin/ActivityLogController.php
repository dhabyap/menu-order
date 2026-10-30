<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::latest()->limit(50)->get();

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
