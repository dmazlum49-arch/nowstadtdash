<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EventRejectedMail;
use App\Models\Event;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Str;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $report = Report::with('reportType')->where('status', 'approved')->get();
            return DataTables::of($report)
                ->addIndexColumn()
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })
                ->addColumn('title', function ($row) {
                    return $row->category->event_category ?? '-';
                })
                ->addColumn('report_type', function ($row) {
                    return $row->reportType->name;
                })
                ->addColumn('location', function ($row) {
                    return $row->location ? Str::limit($row->location, 43) : '-';
                })
                
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            // 'edit' => route('admin.users.edit', $row->id),
                            'delete' => route('admin.reports.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['action', 'about_event'])
                ->make(true);
        }

        return view('admin.report.index', [
            'title' => "Reports",
            'breadcrumbs' => [
                'Reports' => route('admin.reports.index'),
            ]
        ]);
    }

    public function accept($id)
    {
        $report = Report::findOrFail($id);

        $report->update(['status' => 'approved']);

        // Send email
        $user = $report->user;

        $users = User::where('type', 'user')->get();

        foreach ($users as $user) {
            $locale = $user->language ?? 'en';
            App::setLocale($locale);
            send_push($user->id, [
                'push_type' => 1,
                'push_title' => __('your Report are Approved'),
                'push_message' => $report->title,
                'from_user_id' => auth()->id(),
                'extra' => [
                    'event_id' => $report->id,
                ],
            ], false);
        }
        return redirect()->back()->with('success', 'Report accepted successfully.');
    }

    public function reject(Request $request, $id)
    {
        $report = Report::with('user')->findOrFail($id);

        $report->status = 'rejected';
        $report->save();

        $user = $report->user;
        // Send email

        return redirect()->back()->with('success', 'Event rejected and notified via email.');
    }

    public function destroy(Report $report)
    {
        try {
            
            $report->delete();
            return response()->json([
                'success' => true,
                'message' => __('Report has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Report: ') . $e->getMessage(),
            ], 500);
        }
    }
}
