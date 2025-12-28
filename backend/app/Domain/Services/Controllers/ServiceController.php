<?php

namespace App\Domain\Services\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Services\Models\Service;
use App\Domain\Services\Resources\ServiceResource;
use App\Domain\Services\Resources\ServiceListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of active services with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()
            ->active()
            ->with(['category', 'user', 'images']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->inCategory($request->category_id);
        }

        // Filter by category slug
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filter by price range
        if ($request->has('min_price') || $request->has('max_price')) {
            $query->priceBetween($request->min_price, $request->max_price);
        }

        // Filter by delivery time
        if ($request->has('max_delivery_time')) {
            $query->where('delivery_time', '<=', $request->max_delivery_time);
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'popular':
                $query->orderBy('orders_count', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Pagination
        $perPage = min($request->get('per_page', 12), 50);
        $services = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ServiceListResource::collection($services),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    /**
     * Display a single service by slug.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $service = Service::where('slug', $slug)
            ->active()
            ->with(['category', 'user', 'images'])
            ->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        // Increment views
        $service->incrementViews();

        return response()->json([
            'success' => true,
            'data' => new ServiceResource($service),
        ]);
    }
}
