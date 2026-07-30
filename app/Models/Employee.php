<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'id', // we override the auto-increment from webhook
        'employee_code',
        'name',
        'email',
        'phone',
        'status',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
