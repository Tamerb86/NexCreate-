<?php

namespace App\Domain\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Requests\StoreOrderRequest;
use App\Domain\Orders\Resources\OrderResource;
use App\Domain\Orders\Resources\OrderListResource;
use App\Domain\Services\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $request->get('role', 'buyer');

        $query = Order::query()
            ->with(['service', 'buyer', 'creator']);

        // Filter by role
        if ($role === 'creator') {
            $query->byCreator($user->id);
        } else {
            $query->byBuyer($user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        // Sorting
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = min($request->get('per_page', 10), 50);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => OrderListResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Store a newly created order.
     *
     * @param StoreOrderRequest $request
     * @return JsonResponse
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        // Get the service
        $service = Service::with('user')->find($request->service_id);

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }

        // Check if service is active
        if ($service->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This service is not available.',
            ], 400);
        }

        // Prevent ordering own service
        if ($service->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot order your own service.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create order
            $order = Order::create([
                'service_id' => $service->id,
                'buyer_id' => $user->id,
                'creator_id' => $service->user_id,
                'price' => $service->price,
                'requirements' => $request->requirements,
                'status' => Order::STATUS_PENDING,
            ]);

            // Increment service orders count
            $service->increment('orders_count');

            DB::commit();

            // Load relationships
            $order->load(['service.images', 'buyer', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => new OrderResource($order),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        $order = Order::with(['service.images', 'buyer', 'creator', 'deliveries'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Authorization: only buyer, creator, or admin can view
        if (!$order->isParty($user->id) && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }
}
