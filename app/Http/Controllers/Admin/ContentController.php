<?php

namespace App\Http\Controllers\Admin;

use App\Models\Content;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class ContentController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $users = Content::where('language', 'en')->get();
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('content', function ($row) {
                    // Remove HTML tags and limit the content to 100 characters
                    return Str::limit(strip_tags($row->content), 100);
                })
                ->addColumn('action', function ($row) {
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'edit' => route('admin.contents.edit', $row->id),
                            'view' => route('admin.contents.show', $row->id),
                        ]
                    ];

                    // Generate action buttons using the helper function
                    return generate_actions_buttons($params);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.contents.index', [
            'title' => "Contents",
            'breadcrumbs' => [
                'Contents' => route('admin.contents.index'),
            ]
        ]);
    }

    public function show(Content $content)
    {
        return view('admin.contents.show', [
            'title' => "Content",
            'data' => $content,
            'breadcrumbs' => [
                'Content' => route('admin.contents.index'),
                'View' => route('admin.contents.show', $content->id),
            ]
        ]);
    }

    public function edit(Content $content)
    {
        $translations = Content::where('unique_id', $content->unique_id)->get()->keyBy('language');
        return view('admin.contents.edit', [
            'title' => "Content",
            'data' => $content,
            'translations' => $translations,
            'breadcrumbs' => [
                'Content' => route('admin.contents.index'),
                'edit' => route('admin.contents.edit', $content->id),
            ]
        ]);
    }

    public function update(Request $request, Content $content)
    {
        $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'details' => 'required|array',
            'details.*' => 'required|string',
        ]);

        $uniqueId = $content->unique_id;
        $languages = getSupportedLanguages();

        $inputTitles = $request->title;
        $inputDetails = $request->details;

        $defaultTitle = $inputTitles['en'] ?? '';
        $defaultDetail = $inputDetails['en'] ?? '';

        foreach ($languages as $lang => $label) {
            $title = $inputTitles[$lang] ?? $defaultTitle;
            $details = $inputDetails[$lang] ?? $defaultDetail;

            $record = Content::where('unique_id', $uniqueId)->where('language', $lang)->first();

            if ($record) {
                $record->title = $title;
                $record->content = $details;
                $record->save();
            } else {
                Content::create([
                    'title' => $title,
                    'content' => $details,
                    'language' => $lang,
                    'unique_id' => $uniqueId,
                ]);
            }
        }
        flash_session('success', 'Content updated successfully.');
        return redirect()->route('admin.contents.index');
    }
}
