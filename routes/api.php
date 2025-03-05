<?php

use App\Http\Controllers\DevController;

Route::get('/health', [DevController::class, 'checkHealth']);

Route::get('/getAll', [DevController::class, 'getAllItems']);
Route::get('/{id}', [DevController::class, 'getItem']);
Route::post('/create', [DevController::class,'createItem']);
Route::put('/update/{id}', [DevController::class,'updateItem']);
Route::delete('/delete/{id}', [DevController::class,'deleteItem']);
