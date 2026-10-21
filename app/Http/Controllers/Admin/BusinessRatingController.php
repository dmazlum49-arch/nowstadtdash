<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessRating;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BusinessRatingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = BusinessRating::with(['user', 'business'])->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })
                ->addColumn('business_name', function ($row) {
                    return $row->business->business_name ?? '-';
                })
                ->addColumn('rating', function ($row) {
                    $stars = '';
                    for ($i = 1; $i <= 5; $i++) {
                        $stars .= $i <= $row->rating ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>';
                    }
                    return $stars;
                })
                ->addColumn('review', function ($row) {
                    return $row->review ?? '-';
                })
                ->addColumn('moderation', function ($row) {
                    $statusClass = [
                        'pending' => 'bg-warning',
                        'flagged' => 'bg-danger',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-secondary',
                    ];
                    $class = $statusClass[$row->moderation_status] ?? 'bg-info';
                    return '<span class="badge ' . $class . '">' . ucfirst($row->moderation_status) . '</span>';
                })
                ->addColumn('status', function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.business-ratings.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'delete' => route('admin.business-ratings.destroy', $row->id),
                        ]
                    ];
                    
                    $buttons = generate_actions_buttons($params);
                    
                    if ($row->moderation_status === 'flagged' || $row->moderation_status === 'pending') {
                        $approveUrl = route('admin.business-ratings.approve', $row->id);
                        $buttons .= ' <a href="' . $approveUrl . '" class="btn btn-sm btn-outline-success approve-rating" title="Approve"><i class="fas fa-check"></i></a>';
                    }
                    
                    return $buttons;
                })
                ->rawColumns(['rating', 'moderation', 'status', 'action'])
                ->make(true);
        }

        return view('admin.businessRating.index', [
            'title' => "Business Ratings & Reviews",
            'breadcrumbs' => [
                'Business Ratings' => route('admin.business-ratings.index'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $rating = BusinessRating::find($id);
        if ($rating) {
            $rating->update(['status' => ($rating->status == "inactive") ? "active" : "inactive"]);
            return response()->json(['success' => true, 'message' => 'Rating status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Rating not found.']);
    }

    public function approve($id)
    {
        $rating = BusinessRating::findOrFail($id);
        $rating->update(['moderation_status' => 'approved']);
        flash_session('success', 'Rating approved successfully.');
        return back();
    }

    public function destroy($id)
    {
        try {
            $rating = BusinessRating::findOrFail($id);
            $rating->delete();
            return response()->json([
                'success' => true,
                'message' => __('Rating has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Rating: ') . $e->getMessage(),
            ], 500);
        }
    }
}
