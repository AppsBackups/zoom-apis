<?php


namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $fillable = [
        'name',
        'username',
        'phone',
        'password',
        'role',
        'user_type',
        'demo_tokens',
        'live_tokens',
        'admin_id',
        'is_active',
        'level',
        'period',
        'expiry_date',
    ];


    protected $hidden = ['password'];


    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
