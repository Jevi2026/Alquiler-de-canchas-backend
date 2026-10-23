<?php
 
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\CanchaController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
 
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::get('/canchas', [CanchaController::class, 'index']);
Route::get('/canchas/{cancha}/photo', [CanchaController::class, 'photo'])->name('canchas.photo');
 
Route::middleware(['auth:sanctum', 'active.user'])
    ->group(function () {
        Route::get('/user', [AuthController::class, 'me'])
            ->middleware('abilities:profile:read');
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::middleware('role:cliente')->group(function () {
            Route::get('/reservations', [ReservationController::class, 'index']);
            Route::post('/reservations', [ReservationController::class, 'store'])->middleware('throttle:10,1');
            Route::patch('/reservations/{reservation}/reschedule', [ReservationController::class, 'reschedule']);
            Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
        });
        Route::middleware('role:admin')->group(function () {
            Route::post('/admins', [AuthController::class, 'createAdmin']);
            Route::post('/tellers', [AuthController::class, 'createTeller']);
            Route::post('/canchas', [CanchaController::class, 'store']);
            Route::put('/canchas/{cancha}', [CanchaController::class, 'update']);
            Route::patch('/canchas/{cancha}', [CanchaController::class, 'update']);
            Route::delete('/canchas/{cancha}', [CanchaController::class, 'destroy']);
        });
        Route::middleware('role:admin,teller')->group(function () {
            Route::get('/admin/cashier', [CashierController::class, 'summary']);
            Route::get('/admin/pending-reservations', [CashierController::class, 'pendingReservations']);
            Route::post('/admin/reservations/{reservation}/payment', [CashierController::class, 'pay']);
            Route::post('/admin/cashier/expenses', [CashierController::class, 'expense']);
            Route::get('/admin/reports', [ReportController::class, 'index']);
            Route::get('/admin/reports/{type}.pdf', [ReportController::class, 'download'])
                ->where('type', 'sales|reservations|courts|payments|cash');
        });
    });
