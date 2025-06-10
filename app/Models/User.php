<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'lastname',
        'email',
        'phone',
        'password',
        'roles',
        'empresa_id',
    ];

    protected $hidden = [
        'remember_token',
        'reset_token_expires_at',
        'created_at',
        'updated_at'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
