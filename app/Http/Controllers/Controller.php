<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Get per_page limit from request
     */
    protected function getPerPage(Request $request, int $default = 10): int
    {
        return (int) $request->input('per_page', $default);
    }

    /**
     * Standard paginated response for APIs
     */
    protected function paginatedResponse($paginator, string $message = 'Data berhasil diambil.')
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }
}
