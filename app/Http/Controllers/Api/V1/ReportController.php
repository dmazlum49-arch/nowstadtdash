<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ResponseController;
use App\Models\Report;
use App\Models\ReportType;

class ReportController extends ResponseController
{
    public function createEditReport(Request $request)
    {
        $userId = $request->user()->id;
        $this->directValidation([
            'title' => ['nullable'],
            'location' => ['nullable'],
            'latitude' => ['nullable'],
            'longitude' => ['nullable'],
            'report_type_id' => ['required']
        ]);

        $user = $request->user();

        if ($request->filled('report_id')) {
            $report = Report::findOrFail($request->report_id);
            $report->update([
                'title' => $request->title,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'report_type_id' => $request->report_type_id,
                'status' => 'approved',
            ]);

            $message = __('api.success_report_updated');

        } else {

            $reportsLast24Hours = Report::where('user_id', $userId)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            if ($reportsLast24Hours >= 3) {
                return ResponseHelper::send(
                    422,
                    'You can only create 3 reports within 24 hours.'
                );
            }
            $report = Report::create([
                'user_id' => $userId,
                'title' => $request->title,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'report_type_id' => $request->report_type_id,
                'status' => 'approved',
            ]);

            $message = __('api.success_report_created');
        }

        return ResponseHelper::send(200, $message, [
            'id' => $report->id,
            'title' => $report->title,
            'location' => $report->location,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'status' => $report->status,
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
        ]);
    }

    public function reportType(Request $request)
    {
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        $query = ReportType::where('status', 'active');

        $reportTypes = $query->select('id', 'name', 'image')->latest()->limit($limit)->offset($offset)->get();

        return ResponseHelper::send(200, __('api.success'), $reportTypes);
    }

    public function deleteReport(Request $request)
    {
        $this->directValidation([
            'report_id' => ['required', 'exists:reports,id'],
        ]);

        $report = Report::findOrFail($request->report_id);
        $userId = $request->user()->id;

        if ($report->user_id !== $userId) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        $report->delete();

        return ResponseHelper::send(200, __('api.success_report_deleted'));
    }

    public function myReportList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $status = $request->status;
        $reports = Report::where(['user_id' => $userId,'status'=> $status])->latest()->limit($limit)->offset($offset)->get();

        $filteredReports = $reports->map(function ($report) {
            return [
                'id' => $report->id,
                'title' => $report->title,
                'location' => $report->location,
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
                'status' => $report->status,
                'report_type' =>$report->reportType->name,
                'report_type_image' => $report->reportType->image,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success'), $filteredReports);
    }

    public function allReportList(Request $request)
    {
        $user = $request->user();
        $limit  = (int) ($request->limit ?? 10);
        $offset = (int) ($request->offset ?? 0);

        Report::where('status', 'approved')
            ->where('created_at', '<=', now()->subHours(3))
            ->update(['status' => 'expired']);

        
        if ($user) {
            $user->update(['report_last_seen_at' => now()]);
        }

        $reports = Report::with('reportType')->where('status', 'approved')
            ->latest()
            ->skip($offset)
            ->take($limit)
            ->get();

        $data = $reports->map(function ($report) {

            $expiresAt = $report->created_at->addHours(3);
            $remainingMinutes = now()->diffInMinutes($expiresAt, false);

            return [
                'id'          => $report->id,
                'title'       => $report->title,
                'location'    => $report->location,
                'latitude'    => $report->latitude,
                'longitude'   => $report->longitude,
                'status'      => $report->status,
                'report_type' => $report->reportType->name,
                'report_type_image' => $report->reportType->image,
                'expires_at'  => $expiresAt,
                'minutes_left'=> $remainingMinutes > 0 ? $remainingMinutes : 0,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ];
        });

        return ResponseHelper::send(
            200,
            __('api.success_reports_retrieved'),
            $data
        );
    }

}
