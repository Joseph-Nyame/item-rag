<?php


use App\Services\ItemSync;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemChatController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/branch-info', function () {
    return view('branch-info', [
        'branch' => 'DEVELOP',
        'color' => '#2196F3',
        'env' => config('app.env')
    ]);

});

Route::resource('items', ItemController::class);


Route::get('/branch-info', function () {
    return view('branch-info', [
        'branch' => 'PRODUCTION',
        'color' => '#4CAF50',
        'env' => config('app.env')
    ]);
});

Route::get('/items/sync/qdrant', function (ItemSync $itemSync) {
    $count = $itemSync->fullSync();
    return response()->json([
        'message' => "Successfully synced {$count} items to Qdrant"
    ]);
});


Route::post('/items/chat', [ItemChatController::class, 'chat'])->name('items.chat.submit');
