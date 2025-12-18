<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PassbookController;

Route::get('/', function () {
    return '';
});

Route::get('/passes/test', [PassbookController::class, 'generateTestPass']);
