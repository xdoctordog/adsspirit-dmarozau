<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): OrderCollection
    {
        // 1. Strict validation for query parameters
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'email' => ['nullable', 'email'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);

        // 1. Start query with eager loading to prevent N+1 problem
        $query = Order::with('customer')->orderBy('created_at', 'desc');

        // 2. Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 3. Filter by status (Using validated data)
        if (!empty($validated['status'])) {
            // Note: Since the DB column is a string and our model casts it to Enum,
            // passing the Enum object directly works perfectly with Eloquent.
            $query->where('status', $validated['status']);
        }

        // 4. Filter by email (case-insensitive)
        if (!empty($validated['email'])) {
            $email = strtolower($validated['email']);
            $query->whereHas('customer', function ($q) use ($email) {
                $q->whereRaw('LOWER(email) = ?', [$email]);
            });
        }

        // 5. Pagination with safe per_page limit (max 100)
        $perPage = (int) ($validated['per_page'] ?? 20);
        $perPage = min($perPage, 100);

        $orders = $query->paginate($perPage);

        return new OrderCollection($orders);
        // return OrderListResource::collection($orders);
    }

    public function show(string $id): OrderResource
    {
        // Find by marketplace_id and eager load all relationships for the card
        $order = Order::with(['customer', 'items', 'statusHistories'])
            ->where('marketplace_id', $id)
            ->firstOrFail();

        return new OrderResource($order);
    }
}
