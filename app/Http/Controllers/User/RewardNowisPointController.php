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
use App\Models\RewardNowisPoint;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class RewardNowisPointController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $nowispoints = RewardNowisPoint::where('user_id', Auth::user()->primaryId())->get();
            return DataTables::of($nowispoints)
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
                            'view' => route('user.nowis-point.show', $row->id),
                            'edit' => route('user.nowis-point.edit', $row->id),
                            'delete' => route('user.nowis-point.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);

                })
                ->rawColumns(['action', 'description'])
                ->make(true);
        }

        return view('user.nowispoints.index', [
            'title' => "Nowis Points",
            'breadcrumbs' => [
                'Nowis Point' => route('user.nowis-point.index'),
            ]
        ]);
    }

    public function show(RewardNowisPoint $nowis_point)
    {
        return view('user.nowispoints.show', [
            'title' => "Nowis Points",
            'data' => $nowis_point,
            'breadcrumbs' => [
                'Nowis Points' => route('user.nowis-point.index'),
                'View' => route('user.nowis-point.show', $nowis_point),
            ],
        ]);
    }

    public function create()
    {
        $Businesses = Business::where('user_id', Auth::user()->primaryId())->get();
        return view('user.nowispoints.create', [
            'title' => "Nowis Points",
            'Businesses' =>$Businesses,
            'breadcrumbs' => [
                'Nowis Points' => route('user.nowis-point.index'),
                'Add' => route('user.nowis-point.create'),
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
            // 'nowis_points' => 'nullable',
            // 'logo' => 'nullable|image',
            'date' => 'required',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'valid_title' => 'required',
        ]);

        $nowispoints = RewardNowisPoint::create([
            'user_id' => $userId,
            'business_id' => $request->business_id,
            'title' => $request->title,
            'description' => $request->description,
            'nowis_points' => 20,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'valid_title' => $request->valid_title,
        ]);

        // if ($request->hasFile('logo')) {
        //     $logoImagePath = upload_file('logo', 'nowis_point_images');

        //     if ($nowispoints->logo) {
        //         un_link_file($nowispoints->logo);
        //     }

        //     $nowispoints->update(['logo' => $logoImagePath]);
        // }

        flash_session('success', 'Nowis Point created successfully.');
        return redirect()->route('user.nowis-point.index');
        
    }

    public function edit(RewardNowisPoint $nowis_point)
    {
        $Businesses = Business::where('user_id', Auth::user()->primaryId())->get();
        return view('user.nowispoints.edit', [
            'title' => "Nowis Points",
            'Businesses' =>$Businesses,
            'data' => $nowis_point,
            'breadcrumbs' => [
                'Nowis Points' => route('user.nowis-point.index'),
                'Edit' => route('user.nowis-point.edit', $nowis_point->id),
            ],
        ]);
    }

    public function update(Request $request, RewardNowisPoint $nowis_point)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            // 'nowis_points' => 'nullable',
            // 'logo' => 'nullable|image',
            'date' => 'required',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'valid_title' => 'required',
        ]);

        $nowis_point->update([
            'business_id' => $request->business_id,
            'title' => $request->title,
            'description' => $request->description,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'valid_title' => $request->valid_title,
        ]);

        // if ($request->hasFile('logo')) {
        //     $logoImagePath = upload_file('logo', 'nowis_point_images');

        //     if ($nowis_point->logo) {
        //         un_link_file($nowis_point->logo);
        //     }

            //     $nowis_point->update(['logo' => $logoImagePath]);
            // }

        flash_session('success', 'Nowis Point updated successfully.');
        return redirect()->route('user.nowis-point.index');
    }

    public function destroy($id)
    {
        try {
            $nowis_point = RewardNowisPoint::find($id);
            $nowis_point->delete();
            return response()->json([
                'success' => true,
                'message' => __('Nowis Point has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Nowis Point: ') . $e->getMessage(),
            ], 500);
        }
    }

}
