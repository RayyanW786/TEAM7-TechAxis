<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\UserRole;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $table = "users";
    protected $fillable = [
        'name',
        'email',
        'role',
        'password_hash',
    ];
    protected $hidden = [
        'password_hash',
    ];
    protected $casts = [
        'role' => UserRole::class,
        'email_verified_at' => 'datetime',
        'password_must_change' => 'boolean',
        'password_changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    public function setPassword(string $plainPassword, bool $forceChange = false): void
    {
        $this->setPasswordHashViaDb(
            hash: Hash::make($plainPassword),
            forceChange: $forceChange
        );
        $this->refresh();
    }
    public function setPasswordHashViaDb(string $hash, bool $forceChange = false): void
    {
        DB::statement('select fn_user_set_password(?, ?, ?)', [
            $this->id,
            $hash,
            $forceChange,
        ]);
    }
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class, 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    public function defaultShippingAddress()
    {
        return $this->hasOne(Address::class, 'user_id')->where('is_default_shipping', true);
    }

    public function cart()
    {
        return $this->hasOne(ShoppingCart::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function supportTicketsCreated()
    {
        return $this->hasMany(SupportTicket::class, 'created_by_user_id');
    }

    public function supportTicketsAssigned()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to_user_id');
    }

    public function productReviews()
    {
        return $this->hasMany(ProductReview::class, 'user_id');
    }

    public function serviceReview()
    {
        return $this->hasOne(ServiceReview::class, 'user_id');
    }
}
