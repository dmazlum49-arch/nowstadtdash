<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CityController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $city = City::where('language', 'en')->get();
            return DataTables::of($city)
                ->addIndexColumn()
                ->addColumn('city_name', function ($row) {
                    return $row->city_name;
                })
                ->addColumn('status', content: function ($row) {
                    return get_generate_switch($row->status, $row->id, route('admin.city.update-status', $row->id));
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.city.edit', $row->id),
                            // 'view' => route('admin.city.show', $row->id),
                            'delete' => route('admin.city.destroy', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.city.index', [
            'title' => "Cities",
            'breadcrumbs' => [
                'Cities' => route('admin.city.index'),
            ]
        ]);
    }

    public function updateStatus($id)
    {
        $city = City::find($id);
        if ($city) {
            $cities = City::where('unique_id', $city->unique_id)->get();
            foreach ($cities as $ct) {
                $ct->update(['status' => ($ct->status == "inactive") ? "active" : "inactive"]);
                $ct->save();
            }
            $city->update(['status' => ($city->status == "inactive") ? "active" : "inactive"]);
            $city->save();
            return response()->json(['success' => true, 'message' => 'City status updated successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'City not found.']);
    }

    public function show(City $city)
    {
        return view('admin.city.show', [
            'title' => "Cities",
            'data' => $city,
            'breadcrumbs' => [
                'Cities' => route('admin.city.index'),
                'View' => route('admin.city.show', $city->id),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.city.create', [
            'title' => "Cities",
            'breadcrumbs' => [
                'Cities' => route('admin.city.index'),
                'Create' => route('admin.city.create'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'city_name'     => 'required|array',
            'city_name.*'   => 'nullable|string|max:255',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
        ]);

        // Generate unique_id
        do {
            $uniqueId = mt_rand(100000, 999999);
        } while (City::where('unique_id', $uniqueId)->exists());

        $input = $request->city_name;

        // If German (de) name is empty, fallback to English (en)
        if (empty($input['de']) && !empty($input['en'])) {
            $input['de'] = $input['en'];
        }

        foreach ($input as $lang => $name) {
            // Only insert if language text is provided
            if (!empty($name)) {
                City::create([
                    'city_name' => $name,
                    'latitude'  => $request->latitude,
                    'longitude' => $request->longitude,
                    'language'  => $lang,
                    'unique_id' => $uniqueId,
                ]);
            }
        }

        flash_session('success', __('City created successfully'));
        return redirect()->route('admin.city.index');
    }

    public function edit(City $city)
    {
        $translations = City::where('unique_id', $city->unique_id)
            ->pluck('city_name', 'language');
        return view('admin.city.edit', [
            'title' => "Cities",
            'data' => $city,
            'translations' => $translations,
            'breadcrumbs' => [
                'Cities' => route('admin.city.index'),
                'Edit' => route('admin.city.edit', $city->id),
            ],
        ]);
    }

    public function update(Request $request, City $city)
    {
        // dd($request->all());
        $request->validate([
            'city_name' => 'required|array',
            'city_name.*' => 'required|string|max:255',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
        ]);

        $uniqueId = $city->unique_id;

        foreach ($request->city_name as $lang => $name) {
            $category = City::where('unique_id', $uniqueId)
                ->where('language', $lang)
                ->first();

            if ($category) {
                $category->city_name = $name;
                $category->latitude = $request->latitude;
                $category->longitude = $request->longitude;
                $category->save();
            } else {
                City::create([
                    'city_name' => $name,
                    'latitude'  => $request->latitude,
                    'longitude' => $request->longitude,
                    'language' => $lang,
                    'unique_id' => $uniqueId,
                ]);
            }
        }
        flash_session('success', 'City updated successfully.');
        return redirect()->route('admin.city.index');
    }

    public function destroy(City $city)
    {
        try {
            // $city->delete();
            City::where('unique_id', $city->unique_id)->delete();
            return response()->json([
                'success' => true,
                'message' => __('City has been successfully deleted.'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete City: ') . $e->getMessage(),
            ], 500);
        }
    }
}
