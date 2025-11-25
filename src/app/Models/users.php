<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\UserRole;

class users extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $table = "users";
    public $timestamps = false;
    protected $fillable = [
        'name',
        'email',
        'role'
    ] ;
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
}
