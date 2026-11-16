<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;

class EmailLogController extends Controller
{
    public function index()
    {
        $logs = EmailLog::latest()->paginate(25);
        return view('admin.email_logs.index', compact('logs'));
    }
}

