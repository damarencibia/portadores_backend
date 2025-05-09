<?php

// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // Relación inversa con User
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
