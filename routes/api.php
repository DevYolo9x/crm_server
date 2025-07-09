<?php

use App\Http\Controllers\backend\auth\AuthController;
use App\Http\Controllers\backend\candidate\CandidateController;
use App\Http\Controllers\backend\candidate\CandidateJobController;
use App\Http\Controllers\backend\config\ConfigurationController;
use App\Http\Controllers\backend\contract\ContractController;
use App\Http\Controllers\backend\customer\CustomerController;
use App\Http\Controllers\backend\customer\CustomerGroupController;
use App\Http\Controllers\backend\DashboardController;
use App\Http\Controllers\backend\industry\IndustryController;
use App\Http\Controllers\backend\job\JobController;
use App\Http\Controllers\backend\logs\ActivityLogController;
use App\Http\Controllers\backend\permission\PermissionController;
use App\Http\Controllers\backend\roles\RolesController;
use App\Http\Controllers\backend\user\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});
Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics'])->middleware('can:dashboard_index');
    Route::get('/dashboard/chart', [DashboardController::class, 'chart'])->middleware('can:dashboard_index');
    Route::group(['prefix' => 'auth'], function () {
        Route::delete('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::get('/permissions', [AuthController::class, 'permissions']);
        Route::put('/profile-update', [AuthController::class, 'update']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
    Route::group(['prefix' => 'permissions'], function () {
        Route::get('/config', [PermissionController::class, 'config']);
        Route::get('/index', [PermissionController::class, 'index']);
        Route::post('/store', [PermissionController::class, 'store']);
        Route::put('/update/{id}', [PermissionController::class, 'update']);
    });
    //cấu hình
    Route::group(['prefix' => 'config'], function () {
        Route::controller(ConfigurationController::class)->group(function () {
            Route::get('/cities', 'cities');
            Route::get('/languages', 'getLanguages');
            Route::get('/configurations/candidate', 'candidate');
            Route::get('/configurations', 'index')->middleware('can:configurations_edit');
            Route::post('/configurations', 'update')->middleware('can:configurations_edit');
            Route::post('/configurations/upload', 'uploadFile')->middleware('can:configurations_edit');
            Route::get('/config-structure', 'getConfigStructure')->middleware('can:configurations_edit');
        });
    });
    //nhóm nhân viên
    Route::group(['prefix' => 'roles'], function () {
        Route::get('/permission', [RolesController::class, 'permission']);
        Route::get('/', [RolesController::class, 'getRoles']);
        Route::get('/index', [RolesController::class, 'index'])->middleware('can:roles_index');
        Route::post('/store', [RolesController::class, 'store'])->middleware('can:roles_create');
        Route::put('/update/{id}', [RolesController::class, 'update'])->middleware('can:roles_edit');
        Route::delete('/destroy/{id}', [RolesController::class, 'destroy'])->middleware('can:roles_destroy');
        Route::get('/{id}/show', [RolesController::class, 'show'])->middleware('can:roles_index');
    });

    //nhân viên
    Route::group(['prefix' => 'users'], function () {
        Route::get('/lists', [UserController::class, 'lists']);
        Route::get('/index', [UserController::class, 'index'])->middleware('can:users_index');
        Route::post('/store', [UserController::class, 'store'])->middleware('can:users_create');
        Route::post('/update/{id}', [UserController::class, 'update'])->middleware('can:users_edit');
        Route::delete('/destroy/{id}', [UserController::class, 'destroy'])->middleware('can:users_destroy');
        Route::get('/{id}/show', [UserController::class, 'show']);
        Route::get('/search', [UserController::class, 'search'])->middleware('can:users_index');
    });
    //khách hàng
    Route::get('customer-group-lists', [CustomerGroupController::class, 'lists']);
    Route::get('customer-lists', [CustomerController::class, 'lists']);
    Route::get('industries-lists', [IndustryController::class, 'lists']);
    Route::get('industries-lists-lang', [IndustryController::class, 'listsLang']);

    Route::prefix('customer-groups')->group(function () {
        Route::get('/', [CustomerGroupController::class, 'index'])->middleware('can:customer_groups_index');
        Route::post('/', [CustomerGroupController::class, 'store'])->middleware('can:customer_groups_create');
        Route::post('/{id}', [CustomerGroupController::class, 'update'])->middleware('can:customer_groups_edit'); // POST để hỗ trợ file upload
        Route::delete('/{id}', [CustomerGroupController::class, 'destroy'])->middleware('can:customer_groups_destroy');
        Route::get('/{id}/show', [CustomerGroupController::class, 'show']);
    });
    Route::prefix('customers')->group(function () {
        Route::get('/lists', [CustomerController::class, 'lists']);
        Route::get('/', [CustomerController::class, 'index'])->middleware('can:customers_index');
        Route::post('/', [CustomerController::class, 'store'])->middleware('can:customers_create');
        Route::post('/{id}', [CustomerController::class, 'update'])->middleware('can:customers_edit'); // POST để hỗ trợ file upload
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->middleware('can:customers_destroy');
        Route::get('/{id}/show', [CustomerController::class, 'show']);
        Route::delete('/{id}/remove-attachment', [CustomerController::class, 'removeAttachment']);
    });
    Route::prefix('jobs')->group(function () {
        Route::get('/status', [JobController::class, 'status']);
        Route::get('/', [JobController::class, 'index'])->middleware('can:jobs_index');
        Route::post('/', [JobController::class, 'store'])->middleware('can:jobs_create');
        Route::put('/{id}', [JobController::class, 'update'])->middleware('can:jobs_edit'); // POST để hỗ trợ file upload
        Route::delete('/{id}', [JobController::class, 'destroy'])->middleware('can:jobs_destroy');
        Route::get('/{id}/show', [JobController::class, 'show'])->middleware('can:jobs_index');
        Route::get('/{id}/export-pdf', [JobController::class, 'exportPdf'])->middleware('can:jobs_index');
    });
    Route::prefix('industries')->group(function () {
        Route::get('/', [IndustryController::class, 'index'])->middleware('can:industries_index');
        Route::post('/', [IndustryController::class, 'store'])->middleware('can:industries_create');
        Route::post('/{id}', [IndustryController::class, 'update'])->middleware('can:industries_edit'); // POST để hỗ trợ file upload
        Route::delete('/{id}', [IndustryController::class, 'destroy'])->middleware('can:industries_destroy');
        Route::get('/{id}/show', [IndustryController::class, 'show']);
    });
    Route::prefix('candidates')->group(function () {
        Route::get('/lists', [CandidateController::class, 'lists']);
        Route::get('/search', [CandidateController::class, 'search']);
        Route::get('/', [CandidateController::class, 'index'])->middleware('can:candidates_index');
        Route::post('/', [CandidateController::class, 'store'])->middleware('can:candidates_create');
        Route::post('/add-user', [CandidateController::class, 'addUserForCandidate']);
        Route::get('/check-exists', [CandidateController::class, 'checkExists']);
        Route::get('/export-cv', [CandidateController::class, 'exportTemplateBlade'])->middleware('can:candidates_index');
        Route::post('/{id}', [CandidateController::class, 'update'])->middleware('can:candidates_edit'); // POST để hỗ trợ file upload
        Route::delete('/{id}', [CandidateController::class, 'destroy'])->middleware('can:candidates_destroy');
        Route::get('/{id}/show', [CandidateController::class, 'show']);
    });
    Route::prefix('candidate-jobs')->group(function () {
        Route::get('/{job_id}', [CandidateJobController::class, 'index'])->middleware('can:jobs_index');
        Route::post('/{job_id}', [CandidateJobController::class, 'store'])->middleware('can:jobs_index');
        Route::put('/{id}', [CandidateJobController::class, 'update'])->middleware('can:jobs_index'); // POST để hỗ trợ file upload
        Route::delete('/{id}', [CandidateJobController::class, 'destroy'])->middleware('can:jobs_index');
    });
    Route::prefix('contracts')->group(function () {
        Route::get('/lists', [ContractController::class, 'lists']);
        Route::get('/', [ContractController::class, 'index'])->middleware('can:contracts_index');
        Route::post('/', [ContractController::class, 'store'])->middleware('can:contracts_create');
        Route::put('/{id}', [ContractController::class, 'update'])->middleware('can:contracts_edit');
        Route::delete('/{id}', [ContractController::class, 'destroy'])->middleware('can:contracts_destroy');
        Route::get('/{id}/show', [ContractController::class, 'show']);
    });
    Route::get('jobs/{id}/show', [JobController::class, 'show']);
    Route::get('candidate/{id}/show', [CandidateController::class, 'show']);
    Route::get('jobs/{id}/export-pdf', [JobController::class, 'exportPdf'])->name('jobs.export-pdf');
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('can:activity_logs_index');
});

//frontend
Route::group(['prefix' => 'frontend'], function () {});
