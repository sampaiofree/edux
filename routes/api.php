<?php

use App\Http\Controllers\Api\CourseCatalogController;
use App\Http\Controllers\Api\KavooWebhookController;
use App\Http\Controllers\Api\TrackingEventController;
use Illuminate\Support\Facades\Route;

Route::get('cursos', CourseCatalogController::class);
Route::post('kavoo/webhook', KavooWebhookController::class);
Route::post('tracking/events', TrackingEventController::class)->name('api.tracking.events');
