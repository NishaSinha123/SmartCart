<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'order_status',
        'payment_status',
        'payment_method',
        'transaction_id',
        'shipping_address_id',
        'tracking_number',
        'courier_name',
        'expected_delivery_date',
        'delivered_at',
        'cancellation_reason',
        'cancelled_at',
        'return_reason',
        'return_requested_at',
        'return_approved_at',
        'refund_status',
        'shipping_phone',
        'shipping_address_text',
        'payment_screenshot',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
    ];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'return_requested_at' => 'datetime',
        'return_approved_at' => 'datetime',
    ];

    // Order Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RETURN_REQUESTED = 'return_requested';
    const STATUS_RETURN_APPROVED = 'return_approved';
    const STATUS_RETURN_REJECTED = 'return_rejected';
    const STATUS_RETURNED = 'returned';

    // Payment Status Constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_SUBMITTED = 'submitted';
    const PAYMENT_VERIFIED = 'verified';
    const PAYMENT_FAILED = 'failed';

    // Refund Status Constants
    const REFUND_PENDING = 'pending';
    const REFUND_PROCESSING = 'processing';
    const REFUND_COMPLETED = 'completed';
    const REFUND_FAILED = 'failed';

    // Order statuses that are considered "active" (money committed, not yet delivered)
    const ACTIVE_STATUSES = [
        'pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class);
    }

    // Helper Methods
    public function canBeCancelled()
    {
        return in_array($this->order_status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PROCESSING
        ]);
    }

    public function canBeReturned()
    {
        return $this->order_status == self::STATUS_DELIVERED 
            && $this->delivered_at 
            && $this->delivered_at->diffInDays(now()) <= 7;
    }

    public function canRequestReturn()
    {
        return $this->order_status == self::STATUS_DELIVERED 
            && !$this->return_requested_at
            && $this->delivered_at 
            && $this->delivered_at->diffInDays(now()) <= 7;
    }

    public function canProcessReturn()
    {
        return $this->order_status == self::STATUS_RETURN_REQUESTED;
    }

    // Accessors
    public function getStatusBadgeClassAttribute()
    {
        return match($this->order_status) {
            self::STATUS_PENDING, self::STATUS_CONFIRMED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PROCESSING => 'bg-blue-100 text-blue-800',
            self::STATUS_SHIPPED, self::STATUS_OUT_FOR_DELIVERY => 'bg-purple-100 text-purple-800',
            self::STATUS_DELIVERED => 'bg-green-100 text-green-800',
            self::STATUS_CANCELLED, self::STATUS_RETURNED => 'bg-red-100 text-red-800',
            self::STATUS_RETURN_REQUESTED => 'bg-orange-100 text-orange-800',
            self::STATUS_RETURN_APPROVED => 'bg-blue-100 text-blue-800',
            self::STATUS_RETURN_REJECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusIconAttribute()
    {
        return match($this->order_status) {
            self::STATUS_PENDING => 'fa-clock',
            self::STATUS_CONFIRMED => 'fa-check-circle',
            self::STATUS_PROCESSING => 'fa-cogs',
            self::STATUS_SHIPPED => 'fa-shipping-fast',
            self::STATUS_OUT_FOR_DELIVERY => 'fa-truck',
            self::STATUS_DELIVERED => 'fa-check-double',
            self::STATUS_CANCELLED => 'fa-times-circle',
            self::STATUS_RETURN_REQUESTED => 'fa-exchange-alt',
            self::STATUS_RETURN_APPROVED => 'fa-check-circle',
            self::STATUS_RETURN_REJECTED => 'fa-times-circle',
            self::STATUS_RETURNED => 'fa-undo-alt',
            default => 'fa-box',
        };
    }

    /**
     * Get total amount of delivered orders for current month
     * These are fully completed transactions where money is actually spent
     */
    public static function getMonthlyDeliveredSpent($userId)
    {
        return self::where('user_id', $userId)
            ->where('order_status', self::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
    }

    /**
     * Get total amount of active orders (pending, confirmed, processing, shipped)
     * These are orders where money is committed but not yet delivered
     */
    public static function getMonthlyActiveOrdersAmount($userId)
    {
        return self::where('user_id', $userId)
            ->whereIn('order_status', self::ACTIVE_STATUSES)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
    }

    /**
     * Get total amount of cancelled orders for current month
     * These don't affect budget since money is returned
     */
    public static function getMonthlyCancelledAmount($userId)
    {
        return self::where('user_id', $userId)
            ->where('order_status', self::STATUS_CANCELLED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
    }

    /**
     * Calculate total budget commitment = delivered + active orders + cart
     * This gives accurate picture of all money that is or will be spent
     */
    public static function getTotalBudgetCommitment($user)
    {
        $deliveredSpent = self::getMonthlyDeliveredSpent($user->id);
        $activeOrders = self::getMonthlyActiveOrdersAmount($user->id);
        $cartTotal = $user->cart?->items->sum(fn($i) => $i->product->price * $i->quantity) ?? 0;
        
        return [
            'delivered' => $deliveredSpent,
            'active_orders' => $activeOrders,
            'cart_total' => $cartTotal,
            'total_committed' => $deliveredSpent + $activeOrders + $cartTotal
        ];
    }
}