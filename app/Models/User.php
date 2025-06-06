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
        'email',
        'password',
        'empresa_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    // Relación con Empresa (asumiendo que existe un modelo Empresa)
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // Relación muchos a muchos con Role
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
}
