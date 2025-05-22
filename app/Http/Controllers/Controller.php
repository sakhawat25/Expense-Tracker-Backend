<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function successResponse($data = '', $message = '', $status_code = 200) : JsonResponse {
        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => $message,
        ], $status_code);
    }

    protected function errorResponse($data = '', $message = '', $status_code = 500) : JsonResponse {
        return response()->json([
            'status' => false,
            'data' => $data,
            'message' => $message,
        ], $status_code);
    }
}
