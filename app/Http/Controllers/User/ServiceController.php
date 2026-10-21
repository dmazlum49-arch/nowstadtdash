<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Mail\EventRejectedMail;
use App\Models\Business;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventImages;
use App\Models\GeneralSetting;
use App\Models\Post;
use App\Models\PostImages;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $service = Service::where('user_id', Auth::user()->primaryId())->get();
            return DataTables::of($service)
                ->addIndexColumn()
                ->addColumn('business_name', function ($row) {
                    return $row->business->business_name ?? '-';
                })
                ->addColumn('description', function ($row) {
                    $full = strip_tags($row->description);
                    $short = Str::limit($full, 25);
                    return '<span class="desc-preview" style="cursor:pointer;" data-full="' . e($full) . '">' . $short . '</span>';
                })
                ->addColumn('action', function ($row) {
                    
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('user.service.show', $row->id),
                            'edit' => route('user.service.edit', $row->id),
                            'delete' => route('user.service.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);

                })
                ->rawColumns(['action', 'description'])
                ->make(true);
        }

        return view('user.service.index', [
            'title' => "Service",
            'breadcrumbs' => [
                'Service' => route('user.service.index'),
            ]
        ]);
    }

    public function show(Service $service)
    {
        return view('user.service.show', [
            'title' => "Service",
            'data' => $service,
            'breadcrumbs' => [
                'Service' => route('user.service.index'),
                'View' => route('user.service.show', $service),
            ],
        ]);
    }

    public function create()
    {
        $Businesses = Business::where('user_id', Auth::user()->primaryId())->get();
        return view('user.service.create', [
            'title' => "Add Service",
            'Businesses' =>$Businesses,
            'breadcrumbs' => [
                'Service' => route('user.service.index'),
                'Add' => route('user.service.create'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::user()->primaryId();
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable',
            'service_image' => 'nullable|image',
        ]);

        $service = Service::create([
            'user_id' => $userId,
            'business_id' => $request->business_id,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
        ]);

        $message = __('api.success_service_created');

        if ($request->hasFile('service_image')) {
            $serviceImagePath = upload_file('service_image', 'service_images');

            if ($service->service_image) {
                un_link_file($service->service_image);
            }

            $service->update(['service_image' => $serviceImagePath]);
        }

        flash_session('success', 'Service created successfully.');
        return redirect()->route('user.service.index');
        
    }

    public function edit(Service $service)
    {
        $Businesses = Business::where('user_id', Auth::user()->primaryId())->get();
        return view('user.service.edit', [
            'title' => "Edit Service",
            'Businesses' =>$Businesses,
            'data' => $service,
            'breadcrumbs' => [
                'Service' => route('user.service.index'),
                'Edit' => route('user.service.edit', $service->id),
            ],
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable',
            'service_image' => 'nullable|image',
        ]);

        $service->update([
            'business_id' => $request->business_id,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
        ]);

        if ($request->hasFile('service_image')) {
                $bannerImagePath = upload_file('service_image', 'service_images');

                if ($service->service_image) {
                    un_link_file($service->service_image);
                }

                $service->update(['service_image' => $bannerImagePath]);
            }

        flash_session('success', 'Service updated successfully.');
        return redirect()->route('user.service.index');
    }

    public function destroy($id)
    {
        try {
            $service = Service::find($id);
            $service->delete();
            return response()->json([
                'success' => true,
                'message' => __('service has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete service: ') . $e->getMessage(),
            ], 500);
        }
    }

}
