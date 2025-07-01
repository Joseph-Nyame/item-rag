<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemChatController;

Route::get('/', function () {
    return view('welcome');
});


Route::post('/items/chat', [ItemChatController::class, 'chat'])->name('items.chat.submit');