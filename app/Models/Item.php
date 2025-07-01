<?php

namespace App\Models;

use App\Observers\ItemObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([ItemObserver::class])]
class Item extends Model
{
    protected $fillable = ['name', 'description'];
}
