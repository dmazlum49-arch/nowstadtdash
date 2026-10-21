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
use App\Models\RewardStampCard;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class RewardStampCardController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $stampcards = RewardStampCard::where('user_id', Auth::user()->primaryId())->get();
            return DataTables::of($stampcards)
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
                            'view' => route('user.stamp-card.show', $row->id),
                            'edit' => route('user.stamp-card.edit', $row->id),
                            'delete' => route('user.stamp-card.destroy', $row->id),
                        ]
                    ];
                    return generate_actions_buttons($params);

                })
                ->rawColumns(['action', 'description'])
                ->make(true);
        }

        return view('user.stampcards.index', [
            'title' => "Stamp Card",
            'breadcrumbs' => [
                'Stamp Card' => route('user.stamp-card.index'),
            ]
        ]);
    }

    public function show(RewardStampCard $stamp_card)
    {
        return view('user.stampcards.show', [
            'title' => "Stamp Card",
            'data' => $stamp_card,
            'breadcrumbs' => [
                'Stamp Card' => route('user.stamp-card.index'),
                'View' => route('user.stamp-card.show', $stamp_card),
            ],
        ]);
    }

    public function create()
    {
        $Businesses = Business::where('user_id', Auth::user()->primaryId())->get();
        return view('user.stampcards.create', [
            'title' => "Stamp Card",
            'Businesses' =>$Businesses,
            'breadcrumbs' => [
                'Stamp Card' => route('user.stamp-card.index'),
                'Add' => route('user.stamp-card.create'),
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
            'logo' => 'nullable|image',
        ]);

        $stampcards = RewardStampCard::create([
            'user_id' => $userId,
            'business_id' => $request->business_id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->hasFile('logo')) {
            $logoImagePath = upload_file('logo', 'stamp_card_images');

            if ($stampcards->logo) {
                un_link_file($stampcards->logo);
            }

            $stampcards->update(['logo' => $logoImagePath]);
        }

        flash_session('success', 'Stamp Card created successfully.');
        return redirect()->route('user.stamp-card.index');
        
    }

    public function edit(RewardStampCard $stamp_card)
    {
        $Businesses = Business::where('user_id', Auth::user()->primaryId())->get();
        return view('user.stampcards.edit', [
            'title' => "Stamp Card",
            'Businesses' =>$Businesses,
            'data' => $stamp_card,
            'breadcrumbs' => [
                'Stamp Card' => route('user.stamp-card.index'),
                'Edit' => route('user.stamp-card.edit', $stamp_card->id),
            ],
        ]);
    }

    public function update(Request $request, RewardStampCard $stamp_card)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image',
        ]);

        $stamp_card->update([
            'business_id' => $request->business_id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->hasFile('logo')) {
                $logoImagePath = upload_file('logo', 'stamp_card_images');

                if ($stamp_card->logo) {
                    un_link_file($stamp_card->logo);
                }

                $stamp_card->update(['logo' => $logoImagePath]);
            }

        flash_session('success', 'Stamp Card updated successfully.');
        return redirect()->route('user.stamp-card.index');
    }

    public function destroy($id)
    {
        try {
            $stamp_card = RewardStampCard::find($id);
            $stamp_card->delete();
            return response()->json([
                'success' => true,
                'message' => __('Stamp Card has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Stamp Card: ') . $e->getMessage(),
            ], 500);
        }
    }

}
