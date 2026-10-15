<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

abstract class Controller
{
    /**
     * Get per page count from request with safe boundaries.
     */
    protected function getPerPage(Request $request, int $default = 10, int $max = 100): int
    {
        $perPage = (int) $request->input('per_page', $default);

        if ($perPage <= 0) {
            return $default;
        }

        return min($perPage, $max);
    }

    /**
     * Standardized JSON response for paginated results.
     */
    protected function paginatedResponse($paginator, string $message = 'Data berhasil diambil.', int $statusCode = 200): JsonResponse
    {
        $items = ($paginator instanceof LengthAwarePaginator || $paginator instanceof Paginator)
            ? $paginator->items()
            : (is_array($paginator) ? $paginator : (array) $paginator);

        $currentPage = method_exists($paginator, 'currentPage') ? $paginator->currentPage() : 1;
        $perPage = method_exists($paginator, 'perPage') ? $paginator->perPage() : count($items);
        $total = method_exists($paginator, 'total') ? $paginator->total() : count($items);
        $lastPage = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : 1;
        $from = method_exists($paginator, 'firstItem') ? $paginator->firstItem() : (count($items) > 0 ? 1 : null);
        $to = method_exists($paginator, 'lastItem') ? $paginator->lastItem() : count($items);
        $hasMorePages = method_exists($paginator, 'hasMorePages') ? $paginator->hasMorePages() : false;

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $items,
            'pagination' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $from,
                'to' => $to,
                'has_more_pages' => $hasMorePages,
            ],
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ], $statusCode);
    }
}

