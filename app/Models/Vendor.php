<?php

namespace App\Models;

use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'contact_person', 'location', 'last_price'])]
class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;
}
