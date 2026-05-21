<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
        'profile_photo_path',
        'google_id',
        'is_active',
        'admin_notes',
        'blocked_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'blocked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Role checks
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    // Check if user is active (not blocked)
    public function isActive(): bool
    {
        return $this->is_active;
    }

    // Block user
    public function block($reason = null)
    {
        $this->update([
            'is_active' => false,
            'blocked_at' => now(),
            'admin_notes' => $reason
        ]);
    }

    // Unblock user
    public function unblock()
    {
        $this->update([
            'is_active' => true,
            'blocked_at' => null
        ]);
    }

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function budget()
    {
        return $this->hasOne(Budget::class);
    }

    public function shippingAddresses()
    {
        return $this->hasMany(ShippingAddress::class);
    }

    // Accessor for profile photo
    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }
        
        return 'https://ui-avatars.com/api/?background=6366f1&color=fff&name=' . urlencode($this->name);
    }

    // Get total earnings as seller
    public function getTotalEarningsAttribute()
    {
        if (!$this->isSeller()) return 0;
        
        return \App\Models\OrderItem::whereHas('product', function($q) {
            $q->where('seller_id', $this->id);
        })->whereHas('order', function($q) {
            $q->where('order_status', 'delivered');
        })->sum(\DB::raw('price * quantity * 0.90'));
    }
}