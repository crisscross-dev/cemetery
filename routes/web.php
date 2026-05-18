<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\GraveController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MapCalibrationController;

Route::get('/', function () {
    return view('index');
})->name('homepage');

Route::get('teacher', function () {
    return view('teacher.teacher');
})->name('teacherpage');

Route::get('admin', function () {
    return view('admin.admin');
})->name('adminpage');

// Admin authentication routes
Route::get('admin/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
Route::post('admin/login', [AdminController::class, 'login'])->name('admin.login.post');
Route::post('admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

// Protected admin routes
Route::middleware(['admin.auth'])->group(function () {
    Route::get('map', [GraveController::class, 'map'])->name('admin.map');
    Route::get('map-calibration', [MapCalibrationController::class, 'edit'])->name('admin.map-calibration');
    Route::put('map-calibration', [MapCalibrationController::class, 'update'])->name('admin.map-calibration.update');
    Route::delete('map-calibration', [MapCalibrationController::class, 'destroy'])->name('admin.map-calibration.destroy');
    Route::post('graves/{grave}', [GraveController::class, 'update'])->name('graves.update.post');
    Route::patch('graves/{grave}/status', [GraveController::class, 'updateStatus'])->name('graves.updateStatus');
});

// Grave management routes (public read, admin write)
Route::get('cemetery-map/calibration', [GraveController::class, 'calibration'])->name('cemetery-map.calibration');
Route::get('cemetery-map/calibration/current', [MapCalibrationController::class, 'show'])->name('cemetery-map.calibration.current');
Route::get('graves/statuses', [GraveController::class, 'statuses'])->name('graves.statuses');
Route::get('graves', [GraveController::class, 'index'])->name('graves.index');
Route::get('graves/{grave}', [GraveController::class, 'show'])->name('graves.show');

// Route to serve images (fallback for when symlink doesn't work)
Route::get('storage/graves/{filename}', function ($filename) {
    $path = storage_path('app/public/graves/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->name('graves.image');

Route::middleware(['admin.auth'])->group(function () {
    Route::post('graves', [GraveController::class, 'store'])->name('graves.store');
    Route::put('graves/{grave}', [GraveController::class, 'update'])->name('graves.update');
    Route::delete('graves/{grave}', [GraveController::class, 'destroy'])->name('graves.destroy');
});
