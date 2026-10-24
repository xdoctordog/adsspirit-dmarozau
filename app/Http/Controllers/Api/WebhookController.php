<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ProcessWebhookAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Mail\OrderShippedMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProcessedWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class WebhookController extends Controller
{
    public const AMOUNT_REGEX = 'regex:/^-?\d+\.\d{2}$/';

    public function handle(Request $request, ProcessWebhookAction $action): JsonResponse
    {
        $this->validate($request);

        // Extract the order payload from the JSON body
        $payload = $request->json('data.order');
        $eventId = $request->json('id');

        try {
            $action->execute($payload, $eventId);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage());

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function validate(Request $request): void
    {
        // Strict validation based on OpenAPI contract
        $validated = $request->validate([
            'data.order.id' => 'required|string',
            'data.order.number' => 'required|string',
            'data.order.status' => ['required', Rule::enum(OrderStatus::class)],
            'data.order.customer.email' => 'required|email',
            'data.order.customer.name' => 'required|string',
            'data.order.customer.phone' => 'present|nullable|string',
            'data.order.currency' => 'required|string',
            'data.order.items' => 'required|array',
            'data.order.items.*.sku' => 'required|string',
            'data.order.items.*.name' => 'required|string',
            'data.order.items.*.quantity' => 'required|integer',
            'data.order.items.*.price' => ['required', 'string', self::AMOUNT_REGEX],
            'data.order.items.*.subtotal' => ['required', 'string', self::AMOUNT_REGEX],
            'data.order.delivery_price' => ['required', 'string', self::AMOUNT_REGEX],
            'data.order.discount' => ['required', 'string', self::AMOUNT_REGEX],
            'data.order.total' => ['required', 'string', self::AMOUNT_REGEX],
            'data.order.shipment' => 'present|nullable|array',
            'data.order.shipment.carrier' => 'nullable|string',
            'data.order.shipment.tracking_number' => 'nullable|string',
            'data.order.shipment.tracking_url' => 'nullable|string',
            'data.order.paid_at' => 'present|nullable|date',
            'data.order.cancelled_at' => 'present|nullable|date',
            'data.order.cancel_reason' => 'present|nullable|string|max:65535', // max:65535 TEXT in MySQL
            'data.order.created_at' => 'required|date',
            'data.order.updated_at' => 'required|date',
            'data.order.history' => 'required|array',
            'data.order.history.*.status' => ['required', Rule::enum(OrderStatus::class)],
            'data.order.history.*.at' => 'required|date',
        ]);
    }
}
