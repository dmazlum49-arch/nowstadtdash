<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boost;
use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BoostController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $boosts = Boost::with(['business', 'claims'])->latest('id')->get();
            return DataTables::of($boosts)
                ->addIndexColumn()
                ->addColumn('business', function ($row) {
                    return $row->business->business_name ?? 'N/A';
                })
                ->addColumn('multiplier', function ($row) {
                    $multiplier = $row->multiplier ?? 2;
                    return '<span class="badge bg-primary fs-6">' . $multiplier . 'x Nowis</span>';
                })
                ->addColumn('status', function ($row) {
                    if ($row->isActive()) {
                        return '<span class="badge bg-success">Active</span>';
                    }
                    $now = Carbon::now('Europe/Vienna');
                    $start = Carbon::parse("{$row->start_date} {$row->start_time}", 'Europe/Vienna');
                    if ($now->lt($start)) {
                        return '<span class="badge bg-warning">Upcoming</span>';
                    }
                    return '<span class="badge bg-secondary">Expired</span>';
                })
                ->addColumn('claims_count', function ($row) {
                    return $row->claims->count();
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.boost.edit', $row->id),
                            'view' => route('admin.boost.show', $row->id),
                            'delete' => route('admin.boost.destroy', $row->id),
                        ]
                    ];

                    return generate_actions_buttons($params);
                })
                ->rawColumns(['multiplier', 'status', 'action'])
                ->make(true);
        }

        return view('admin.boost.index', [
            'title' => "Boosts",
            'breadcrumbs' => [
                'Boosts' => route('admin.boost.index'),
            ]
        ]);
    }

    public function show(Boost $boost)
    {
        $boost->load(['business', 'claims.user']);

        return view('admin.boost.show', [
            'title' => "View Boost",
            'breadcrumbs' => [
                'Boosts' => route('admin.boost.index'),
                'View' => route('admin.boost.show', $boost->id),
            ],
            'data' => $boost
        ]);
    }

    public function create()
    {
        $businesses = Business::where(['status' => 'active','type' => 'approved'])->get();
        return view('admin.boost.create', [
            'title' => "Boosts",
            'breadcrumbs' => [
                'Boosts' => route('admin.boost.index'),
                'Create' => route('admin.boost.create'),
            ],
            'businesses' => $businesses
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_ids'   => 'required|array',
            'business_ids.*' => 'exists:businesses,id',
            'start_date'     => 'required|date',
            'start_time'     => 'required',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'end_time'       => 'required',
            'multiplier'     => 'required|in:2,4,8,16',
            'label'          => 'nullable|string|max:255',
        ]);

        $multiplier = (int) $request->multiplier;
        $label = $request->filled('label') ? $request->label : ($multiplier . 'x Nowis');
        $points = 10 * $multiplier;

        foreach ($request->business_ids as $business_id) {
            Boost::create([
                'business_id' => $business_id,
                'start_date'  => $request->start_date,
                'start_time'  => $request->start_time,
                'end_date'    => $request->end_date,
                'end_time'    => $request->end_time,
                'multiplier'  => $multiplier,
                'points'      => $points,
                'label'       => $label,
            ]);
        }

        flash_session('success', 'Boost(s) created successfully.');
        return redirect()->route('admin.boost.index');
    }

    public function edit(Boost $boost)
    {
        $businesses = Business::where(['status' => 'active','type' => 'approved'])->get();
        return view('admin.boost.edit', [
            'title' => "Edit Boost",
            'breadcrumbs' => [
                'Boosts' => route('admin.boost.index'),
                'Edit' => route('admin.boost.edit', $boost->id),
            ],
            'data' => $boost,
            'businesses' => $businesses
        ]);
    }

    public function update(Request $request, Boost $boost)
    {
        $request->validate([
            'business_ids'   => 'required|array',
            'business_ids.*' => 'exists:businesses,id',
            'start_date'     => 'required|date',
            'start_time'     => 'required',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'end_time'       => 'required',
            'multiplier'     => 'required|in:2,4,8,16',
            'label'          => 'nullable|string|max:255',
        ]);

        $multiplier = (int) $request->multiplier;
        $label = $request->filled('label') ? $request->label : ($multiplier . 'x Nowis');
        $points = 10 * $multiplier;

        $boost->update([
            'business_id' => $request->business_ids[0],
            'start_date'  => $request->start_date,
            'start_time'  => $request->start_time,
            'end_date'    => $request->end_date,
            'end_time'    => $request->end_time,
            'multiplier'  => $multiplier,
            'points'      => $points,
            'label'       => $label,
        ]);

        flash_session('success', 'Boost(s) updated successfully.');
        return redirect()->route('admin.boost.index');
    }

    public function destroy(Boost $boost)
    {
        try {
            $boost->delete();
            return response()->json([
                'success' => true,
                'message' => __('Boost has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Boost: ') . $e->getMessage(),
            ], 500);
        }
    }
}
