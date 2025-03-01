<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    // Define the STATUSES constant
    const STATUSES = [
        'pending' => 'warning',
        'active' => 'success', 
        'inactive' => 'danger'
    ];

    // Specify which attributes can be mass assigned
    protected $fillable = [
        'name', 'email', 'status', 'phone', 'source'
    ];

    // Optional: Add a method to get status color
    public function getStatusColorAttribute()
    {
        return self::STATUSES[$this->status] ?? 'secondary';
    }
}