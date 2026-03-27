<?php

use App\Http\Controllers\Api\CourseCatalogController;
use App\Http\Controllers\Api\PaymentWebhookIngressController;
use App\Http\Controllers\Api\TrackingEventController;
use Illuminate\Support\Facades\Route;

Route::get('cursos', CourseCatalogController::class);
Route::match(['get', 'post'], 'webhooks/in/{endpoint_uuid}', PaymentWebhookIngressController::class)
    ->name('api.webhooks.in');
Route::post('tracking/events', TrackingEventController::class)->name('api.tracking.events');
