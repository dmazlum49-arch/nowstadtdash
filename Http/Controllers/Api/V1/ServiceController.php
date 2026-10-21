<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\ResponseController;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends ResponseController
{
    public function createEditService(Request $request)
    {
        $userId = $request->user()->id;
        $this->directValidation([
            'business_id' => 'required|exists:businesses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable',
            'service_image' => 'nullable|image',
            'service_id' => ['nullable', 'exists:services,id'],
        ]);

        if ($request->filled('service_id')) {
            $service = Service::findOrFail($request->service_id);
            $service->update([
                'business_id' => $request->business_id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
            ]);

            $message = __('api.success_service_updated');
        } else {
            $service = Service::create([
                'user_id' => $userId,
                'business_id' => $request->business_id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
            ]);

            $message = __('api.success_service_created');
        }

        if ($request->hasFile('service_image')) {
            $serviceImagePath = upload_file('service_image', 'service_images');

            if ($service->service_image) {
                un_link_file($service->service_image);
            }

            $service->update(['service_image' => $serviceImagePath]);
        }

        return ResponseHelper::send(200, $message, [
            'id' => $service->id,
            'title' => $service->title,
            'description' => $service->description,
            'price' => $service->price,
            'business_name' => $service->business->business_name ?? null,
            'service_image' => $service->service_image ? url($service->service_image) : null,
            'created_at' => $service->created_at,
            'updated_at' => $service->updated_at,
        ]);
    }

    public function myServiceList(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;
        $search = $request->search;
        $business_id = $request->business_id;

        $services = Service::with('business')
            ->where('user_id', $userId)
            ->where('business_id', $business_id)
            ->when($search, function ($query, $search) {
                $query->whereHas('business', function ($q) use ($search) {
                    $q->where('business_name', 'LIKE', "%{$search}%");
                });
            })
            ->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();
        $filterServices = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'title' => $service->title,
                'description' => $service->description,
                'price' => $service->price,
                'business_name' => $service->business->business_name ?? null,
                'service_image' => $service->service_image ? url($service->service_image) : null,
                'created_at' => $service->created_at,
                'updated_at' => $service->updated_at,
            ];
        });

        return ResponseHelper::send(200, __('api.success_services_retrieved'), $filterServices);
    }

    public function serviceDetails(Request $request)
    {
        $this->directValidation([
            'service_id' => 'required|exists:services,id',
        ]);

        $service = Service::with('business')->findOrFail($request->service_id);

        return ResponseHelper::send(200, __('api.success_services_retrieved'), [
            'id' => $service->id,
            'title' => $service->title,
            'description' => $service->description,
            'price' => $service->price,
            'business_name' => $service->business->business_name ?? null,
            'service_image' => $service->service_image ? url($service->service_image) : null,
            'created_at' => $service->created_at,
            'updated_at' => $service->updated_at,
        ]);
    }

    public function deleteService(Request $request)
    {
        $this->directValidation([
            'service_id' => 'required|exists:services,id',
        ]);

        $service = Service::findOrFail($request->service_id);
        if ($service->user_id !== $request->user()->id) {
            return ResponseHelper::send(403, __('api.error_unauthorized'));
        }

        if ($service->service_image) {
            un_link_file($service->service_image);
        }

        $service->delete();

        return ResponseHelper::send(200, __('api.success_service_deleted'));
    }
}
