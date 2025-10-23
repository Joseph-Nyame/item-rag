<?php

namespace App\Models;

use Orbit\Concerns\Orbital;
use App\Observers\ItemObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ObservedBy([ItemObserver::class])]
class Item extends Model
{
    use Orbital, HasFactory;

    protected $fillable = ['name', 'description'];

    public static function schema(Blueprint $table)
    {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getIncrementing()
    {
        return true;
    }
}
