<?php


use App\Services\ItemSync;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemChatController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('items', ItemController::class);

Route::get('/items/sync/qdrant', function (ItemSync $itemSync) {
    $count = $itemSync->fullSync();
    return response()->json([
        'message' => "Successfully synced {$count} items to Qdrant"
    ]);
});


Route::post('/items/chat', [ItemChatController::class, 'chat'])->name('items.chat.submit');