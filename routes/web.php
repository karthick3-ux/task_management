<?php

// =============================================================================
// WEB ROUTES (routes/web.php)
// =============================================================================



use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Dashboard\TasksDashboard;
use App\Livewire\Dashboard\AdminDashboard;


use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Departments\Index as DepartmentsIndex;
use App\Livewire\Roles\Index as RolesIndex;

use App\Livewire\Projects\Index as ProjectIndex;
use App\Livewire\Tasks\Index as TaskIndex;




use App\Livewire\Reports\ProjectReport;
use App\Livewire\Reports\EmployeeReport;







/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Guest routes (only accessible when not authenticated)
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Authenticated routes (only accessible when authenticated)
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', TasksDashboard::class)->name('dashboard');

       Route::get('/admin_dashboard', AdminDashboard::class)->name('admin_dashboard');

 // User Management - Employee Management
    Route::get('/users', UsersIndex::class)
        ->name('users.index')
        ->middleware('can:view_users');
    
    Route::get('/employees', UsersIndex::class)
        ->name('employees.index')
        ->middleware('can:view_users');

Route::get('/roles',RolesIndex::class)
    ->name('roles.index')
    ->middleware('can:manage_roles');
    
    // Profile Management 
    Route::get('/profile', function() {
        return view('profile.edit'); 
    })->name('profile.edit');

       Route::get('/departments', DepartmentsIndex::class)
        ->name('departments.index')
        ->middleware('can:view_departments');


        // Project Management Routes
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', ProjectIndex::class)
            ->name('index')
            ->middleware('can:view_projects');
    });
    
    // Task Management Routes
    // Route::prefix('tasks')->name('tasks.')->group(function () {
    //     Route::get('/', TaskIndex::class)
    //         ->name('index')
    //         ->middleware('can:view_tasks');
    // });
        
     Route::get('/reports/projects', ProjectReport::class)
        ->name('reports.projects')
        ->middleware('can:view_project_report');
    
    // Employee Reports  
    Route::get('/reports/employees', EmployeeReport::class)
        ->name('reports.employees')
        ->middleware('can:view_employee_reports');

        
    // Logout route 
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'Logged out successfully');
    })->name('logout');
});



