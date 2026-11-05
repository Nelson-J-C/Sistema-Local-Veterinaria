<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\VaccineCardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/attachments/{attachment}/download', AttachmentDownloadController::class)
    ->middleware('auth')
    ->name('attachments.download');

Route::get('/patients/{patient}/vaccine-card', VaccineCardController::class)
    ->middleware('auth')
    ->name('patients.vaccine-card');

Route::get('/invoices/{invoice}/receipt', ReceiptController::class)
    ->middleware('auth')
    ->name('invoices.receipt');
