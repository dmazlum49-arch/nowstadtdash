<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ServiceController extends Controller
{

    public function index()
    {
        if (request()->ajax()) {
            $service = Service::with('business')->get();
            return DataTables::of($service)
                ->addIndexColumn()
                ->addColumn('title', function ($row) {
                    return $row->title;
                })
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '';
                })
                ->addColumn('business_name', function ($row) {
                    return $row->business->business_name ?? '';
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('admin.service.show', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.service.index', [
            'title' => "Services",
            'breadcrumbs' => [
                'Services' => route('admin.service.index'),
            ]
        ]);
    }

    public function show(Service $service)
    {
        $service = Service::with('business')->findOrFail($service->id);
        return view('admin.service.show', [
            'title' => "Services",
            'data' => $service,
            'breadcrumbs' => [
                'Services' => route('admin.service.index'),
                'View' => route('admin.service.show', $service->id),
            ],
        ]);
    }

}
