<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'superid',
        'superdeviceid',
        'eventname',
        'eventdescription',
        'eventlevel',
        'meetingid',
        'meetingpassword',
        'sportstype',
        'estatus'
    ];

    protected $casts = [
        'estatus' => 'boolean',
    ];

    // Link to the superadmin or user who created it
    public function superadmin()
    {
        return $this->belongsTo(User::class, 'superid');
    }
}
