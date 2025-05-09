<?php

// app/Models/RoleUser.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    use HasFactory;

    protected $table = 'role_user';

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
    protected $fillable = ['user_id', 'role_id'];
}
