<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\EndlessController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\SoloController;
use App\Http\Controllers\TrainingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PlayerController::class, 'title']);
Route::get('/player', [PlayerController::class, 'form']);
Route::post('/player', [PlayerController::class, 'store']);

Route::get('/character', [CharacterController::class, 'index']);
Route::post('/character', [CharacterController::class, 'select']);
Route::post('/character/allocate', [CharacterController::class, 'allocate']);
Route::post('/character/reset', [CharacterController::class, 'resetPoints']);
Route::post('/character/equip', [CharacterController::class, 'equip']);
Route::post('/character/upgrade', [CharacterController::class, 'upgrade']);

Route::get('/record', [RecordController::class, 'index']);

Route::post('/solo/defeat', [SoloController::class, 'defeat']);
Route::post('/solo/finish', [SoloController::class, 'finish']);
Route::get('/solo/result/{run}', [SoloController::class, 'result']);
Route::get('/solo/{stage?}', [SoloController::class, 'show']);

Route::get('/training', [TrainingController::class, 'show']);
Route::post('/training/finish', [TrainingController::class, 'finish']);
Route::get('/training/result/{run}', [TrainingController::class, 'result']);

Route::post('/endless/finish', [EndlessController::class, 'finish']);
Route::get('/endless/result/{run}', [EndlessController::class, 'result']);
Route::get('/endless/{mode}', [EndlessController::class, 'show']);

Route::get('/matching', [MatchController::class, 'matching']);
Route::post('/matching/join', [MatchController::class, 'join']);
Route::get('/matching/status', [MatchController::class, 'status']);
Route::post('/matching/cancel', [MatchController::class, 'cancel']);

Route::get('/match/{matchId}', [MatchController::class, 'show']);
Route::post('/match/{matchId}/start', [MatchController::class, 'start']);
Route::post('/match/{matchId}/progress', [MatchController::class, 'progress']);
Route::post('/match/{matchId}/finish', [MatchController::class, 'finish']);
Route::get('/match/{matchId}/result', [MatchController::class, 'result']);