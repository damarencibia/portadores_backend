<?php

// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lastname',
        'ci',
        'address',
        'email',
        'phone',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // Relación muchos a muchos con Role
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
}
