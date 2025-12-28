<?php

namespace App\Domain\Services\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Services\Models\Service;
use App\Domain\Services\Models\ServiceImage;
use App\Domain\Services\Requests\StoreServiceRequest;
use App\Domain\Services\Requests\UpdateServiceRequest;
use App\Domain\Services\Requests\UpdateServiceStatusRequest;
use App\Domain\Services\Resources\ServiceResource;
use App\Domain\Services\Resources\ServiceListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreatorServiceController extends Controller
{
    /**
     * Display a listing of the creator's services.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is a creator
        if (!$user->isCreator() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only creators can access this resource.',
            ], 403);
        }

        $query = Service::query()
            ->byCreator($user->id)
            ->with(['category', 'images']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = min($request->get('per_page', 10), 50);
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
     * Store a newly created service.
     *
     * @param StoreServiceRequest $request
     * @return JsonResponse
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is a creator
        if (!$user->isCreator()) {
            return response()->json([
                'success' => false,
                'message' => 'Only creators can create services.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Create service
            $service = Service::create([
                'user_id' => $user->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
                'delivery_time' => $request->delivery_time,
                'revisions' => $request->revisions,
                'status' => Service::STATUS_DRAFT,
            ]);

            // Create images if provided
            if ($request->has('images') && is_array($request->images)) {
                foreach ($request->images as $index => $imageUrl) {
                    ServiceImage::create([
                        'service_id' => $service->id,
                        'url' => $imageUrl,
                        'sort_order' => $index,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            DB::commit();

            // Load relationships
            $service->load(['category', 'user', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Service created successfully.',
                'data' => new ServiceResource($service),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified service.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        $service = Service::with(['category', 'user', 'images'])->find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        // Check ownership
        if ($service->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Update the specified service.
     *
     * @param UpdateServiceRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateServiceRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        // Check ownership
        if ($service->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Update service
            $service->update($request->only([
                'title',
                'description',
                'price',
                'delivery_time',
                'revisions',
                'category_id',
            ]));

            // Update images if provided
            if ($request->has('images') && is_array($request->images)) {
                // Delete existing images
                $service->images()->delete();

                // Create new images
                foreach ($request->images as $index => $imageUrl) {
                    ServiceImage::create([
                        'service_id' => $service->id,
                        'url' => $imageUrl,
                        'sort_order' => $index,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            DB::commit();

            // Reload relationships
            $service->load(['category', 'user', 'images']);

            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully.',
                'data' => new ServiceResource($service),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the service status.
     *
     * @param UpdateServiceStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(UpdateServiceStatusRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        // Check ownership
        if ($service->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $service->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Service status updated successfully.',
            'data' => [
                'id' => $service->id,
                'status' => $service->status,
            ],
        ]);
    }

    /**
     * Delete the specified service.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();

        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        // Check ownership
        if ($service->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        // Check if service has orders
        if ($service->orders_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete service with existing orders.',
            ], 400);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully.',
        ]);
    }
}
