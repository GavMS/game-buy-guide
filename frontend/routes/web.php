<?php

use App\Http\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AgentController::class, 'index'])->name('agent.index');
Route::post('/check/initiate', [AgentController::class, 'initiateCheck'])->name('agent.initiate');
Route::get('/results/{id}', [AgentController::class, 'results'])->name('agent.results');
Route::get('/check-status/{id}', [AgentController::class, 'checkStatus'])->name('agent.status');

