<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = [
            'event' => $request->query('event', ''),
            'email' => $request->query('email', ''),
            'from' => $request->query('from', ''),
            'to' => $request->query('to', ''),
        ];

        return view('admin.audit-logs.index', [
            'logs' => AuditLog::searchForAdmin($filters),
            'filters' => $filters,
            'events' => AuditLog::knownEvents(),
        ]);
    }
}
