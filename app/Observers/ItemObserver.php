<?php

namespace App\Observers;


use App\Models\Item;
use App\Services\ItemSync;

class ItemObserver
{
    public function __construct(
        private ItemSync $itemSync
    ) {}

    public function created(Item $item)
    {
        $this->itemSync->syncSingle($item);
    }

    public function updated(Item $item)
    {
        $this->itemSync->syncSingle($item);
    }

    public function deleted(Item $item)
    {
        $this->itemSync->deleteSingle($item->id);
    }

    public function restored(Item $item)
    {
        $this->itemSync->syncSingle($item);
    }
}