<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ChangeBusinessQrCode;
use Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Mail\EventRejectedMail;
use App\Mail\NowisPointQrMail;

class ChangeBusinessQrCodeController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $business = ChangeBusinessQrCode::with(['business','user'])->where('type', 'pending')->get();
            // dd($business);
            return DataTables::of($business)
                ->addIndexColumn()
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? '-';
                })
                
                ->addColumn('business_name', function ($row) {
                    return $row->business->business_name;
                })
                ->addColumn('description', function ($row) {
                    $full = strip_tags($row->description);
                    $short = Str::limit($full, 25);
                    return '<span class="desc-preview" style="cursor:pointer;" data-full="' . e($full) . '">' . $short . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $acceptUrl = route('admin.admin.business.accept', $row->id);
                    $rejectUrl = route('admin.admin.business.reject', $row->id);
                    $params = [
                        'id' => $row->id,
                        'url' => [
                            'view' => route('admin.business.show', $row->business_id),
                        ]
                    ];
                    $defaultButtons = generate_actions_buttons($params);

                    // Custom Accept/Reject buttons
                    $customButtons = '
                        <a href="' . $acceptUrl . '" class="font-size-14 me-2 btn btn-sm btn-outline-success waves-effect waves-light btnAction" data-action="approved">
                            <i class="fas fa-check"></i>
                        </a>
                        <a href="' . $rejectUrl . '" class="font-size-14 me-2 btn btn-sm btn-outline-danger waves-effect waves-light btnAction" data-action="rejected">
                            <i class="fas fa-times"></i>
                        </a>
                    ';

                    // Combine and return
                    return $defaultButtons . $customButtons;
                })
                ->rawColumns(['description','action'])
                ->make(true);
        }

        return view('admin.QRCodeChange.index', [
            'title' => "Change Business QR Code",
            'breadcrumbs' => [
                'Change Business QR Code' => route('admin.business.qr.change.index'),
            ]
        ]);
    }

    public function accept($id)
    {

        $request = ChangeBusinessQrCode::with(['user','business'])->findOrFail($id);

        $request->update(['type' => 'approved']);

        $qrContent = route('business.qr.scan', ['id' => $request->business->id]);
        $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode($qrContent) . '&size=200';

        $qrImageData = Http::get($qrCodeUrl)->body();

        // Save it temporarily
        $tempPath = public_path('uploads/qrcodes/');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $fileName = 'business_qr_' . $request->business->id . '_' . time() . '.png';
        $filePath = $tempPath . $fileName;
        file_put_contents($filePath, $qrImageData);

        $request->business->update([
        'qr_code'     => 'uploads/qrcodes/' . $fileName,
        'is_active_qr' => true,
        ]);

        Mail::to($request->user->email)->send(new NowisPointQrMail($request->business, $filePath));

        return redirect()->back()->with('success', 'Request accepted and email sent successfully.');
    }

    public function reject(Request $request, $id)
    {
        $requests = ChangeBusinessQrCode::with(['user','business'])->findOrFail($id);

        $requests->type = 'rejected';
        $requests->save();

        $user = $requests->user;
        // Send email
        Mail::to($user->email)->send(new EventRejectedMail($user->name, $requests->business->business_name, $request->input('reason')));

        return redirect()->back()->with('success', 'REquest rejected and notified via email.');
    }
}
