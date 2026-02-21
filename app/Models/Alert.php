<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'alerts';

    // Primary key
    protected $primaryKey = 'massageid';

    // Primary key is not auto-incrementing
    public $incrementing = true;

    // Primary key type
    protected $keyType = 'int';

    // Fillable fields for mass assignment
    protected $fillable = [
        'massagetitle',
        'description',
        'mlink',
        'mtype'
    ];

    // Timestamps enabled
    public $timestamps = true;
}
