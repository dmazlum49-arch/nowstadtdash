<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EventCategoryController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $category = EventCategory::where('language', 'en')->get();
            return DataTables::of($category)
                ->addIndexColumn()
                ->addColumn('event_category', function ($row) {
                    return $row->event_category;
                })
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.event-category.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.event-category.edit', $row->id),
                            // 'view' => route('admin.event-category.show', $row->id),
                            'delete' => route('admin.event-category.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.eventCategory.index', [
            'title' => "Event Categories",
            'breadcrumbs' => [
                'Event Categories' => route('admin.event-category.index'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $category = EventCategory::find($id);
        if ($category) {
            $categories = EventCategory::where('unique_id', $category->unique_id)->get();
            foreach ($categories as $cat) {
                $cat->update(['status' => ($cat->status == "inactive") ? "active" : "inactive"]);
                $cat->save();
            }
            $category->update(['status' => ($category->status == "inactive") ? "active" : "inactive"]);
            $category->save();
            return response()->json(['success' => true, 'message' => 'Event Category status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'Event Category not found.']);
    }

    public function show(EventCategory $event_category)
    {
        return view('admin.eventCategory.show', [
            'title' => "Event Categories",
            'data' => $event_category,
            'breadcrumbs' => [
                'Event Categories' => route('admin.event-category.index'),
                'View' => route('admin.event-category.show', $event_category->id),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.eventCategory.create', [
            'title' => "Event Categories",
            'breadcrumbs' => [
                'Event Categories' => route('admin.event-category.index'),
                'Create' => route('admin.event-category.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_category' => 'required|array',
            'event_category.*' => 'nullable|string|max:255',
        ]);

        do {
            $uniqueId = mt_rand(100000, 999999);
        } while (EventCategory::where('unique_id', $uniqueId)->exists());

        $input = $request->event_category;

        if (empty($input['de'])) {
            $input['de'] = $input['en'];
        }

        foreach ($input as $lang => $name) {
            EventCategory::create([
                'event_category' => $name ?? '',
                'language' => $lang,
                'unique_id' => $uniqueId,
            ]);
        }

        flash_session('success', __('Event Category created successfully'));
        return redirect()->route('admin.event-category.index');

    }

    public function edit(EventCategory $event_category)
    {
        $translations = EventCategory::where('unique_id', $event_category->unique_id)
            ->pluck('event_category', 'language');
        return view('admin.eventCategory.edit', [
            'title' => "Event Categories",
            'data' => $event_category,
            'translations' => $translations,
            'breadcrumbs' => [
                'Event Categories' => route('admin.event-category.index'),
                'Edit' => route('admin.event-category.edit', $event_category->id),
            ],
        ]);
    }

    public function update(Request $request, EventCategory $event_category)
    {
        $request->validate([
            'event_category' => 'required|array',
            'event_category.*' => 'nullable|string|max:255',
        ]);

        $uniqueId = $event_category->unique_id;

        foreach ($request->event_category as $lang => $name) {
            $category = EventCategory::where('unique_id', $uniqueId)
                ->where('language', $lang)
                ->first();

            if ($category) {
                $category->event_category = $name ?? '';
                $category->save();
            } else {
                EventCategory::create([
                    'event_category' => $name ?? '',
                    'language' => $lang,
                    'unique_id' => $uniqueId,
                ]);
            }
        }
        flash_session('success', 'Event Category updated successfully.');
        return redirect()->route('admin.event-category.index');
    }

    public function destroy(EventCategory $event_category)
    {
        try {
            // $event_category->delete();
            EventCategory::where('unique_id', $event_category->unique_id)->delete();
            return response()->json([
                'success' => true,
                'message' => __('Event Category has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete Event Category: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function checkName(Request $request)
    {
        $event_category = $request->val;

        $exists = EventCategory::where('event_category', $event_category)->exists();

        return response()->json(!$exists);
    }

    public function checkNameInEdit(Request $request)
    {
        $id = $request->id;
        $name = $request->val;

        $current = EventCategory::find($id);
        if (!$current) {
            return response()->json(['status' => false, 'message' => 'Invalid record'], 404);
        }

        $exists = EventCategory::where('event_category', $name)
            ->where('unique_id', '!=', $current->unique_id)
            ->exists();

        return response()->json(!$exists);
    }
}
