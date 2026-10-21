<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Send a JSON response
     *
     * @param int $status
     * @param string $message
     * @param mixed $responseData
     * @param array|null $extraData
     * @return \Illuminate\Http\JsonResponse
     */
    public static function send($status, $message = '', $responseData = null, $extraData = null)
    {
        $data = [
            'status' => $status,
            'message' => $message,
            'data' => !empty($responseData) ? $responseData : new \stdClass(),
        ];

        // Merge extra data into response if provided
        if (!empty($extraData) && is_array($extraData)) {
            $data = array_merge($data, $extraData);
        }

        // Determine the appropriate header status
        $validStatus = [200, 401, 412]; // Define valid statuses
        $headerStatus = in_array($status, $validStatus) ? $status : 412;

        // Send the JSON response
        response()->json($data, $headerStatus)
            ->header('Content-Type', 'application/json')->send();
        die(0);
    }
}
