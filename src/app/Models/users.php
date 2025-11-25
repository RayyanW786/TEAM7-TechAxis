<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class users extends Authenticatable
{
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

    public function setPassword(string $plainPassword, bool $forceChange = false): void
    {
        $hashedPassword = Hash::make($plainPassword);
        DB::statement('SELECT fn_user_set_password(?, ?, ?)', [
            $this->id,
            $hashedPassword,
            $forceChange
        ]);
        $this->refresh();
    }
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
}
