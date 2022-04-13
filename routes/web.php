<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AppController::class, 'index']);


Route::get('/setup', [AppController::class, 'setup_company'])->name('setup');
Route::post('/setup', [AppController::class, 'create_admin'])->name('setup.create');

Route::get('/users/invitations/accept', [InvitationController::class, 'accept']);
Route::post('/users/invitations/accept', [InvitationController::class, 'handle_accept'])->name('invitation.accept');

Route::group(['middleware' => ['auth:sanctum', 'verified']], function() {

    Route::get('/dashboard', function () { return Inertia::render('Dashboard'); })->name('dashboard');
    Route::get('/tool/settings', function () { return Inertia::render('ToolSettings'); })->name('tool.settings');

    //Invitations
    Route::get('/users/invitations', [InvitationController::class, 'index'])->name('user.invitations');
    Route::get('/users/invitations/invite', [InvitationController::class, 'invite'])->name('user.invite');
    Route::get('/users/invitations/{invitation}/edit', [InvitationController::class, 'edit'])->name('user.invitations.edit');
    Route::post('/users/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::patch('/users/invitations/{invitation}', [InvitationController::class, 'update']);
    Route::delete('/users/invitations/{invitation}', [InvitationController::class, 'destroy']);

    //Users
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/{user}', [UserController::class, 'edit'])->name('user.edit');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    //Departments
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments');
    Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.profile');
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.edit');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);

    //Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit']);
    Route::patch('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    //Checklists
    Route::get('/checklists/create', [ChecklistController::class, 'create'])->name('checklists.create');
    Route::post('/checklists', [ChecklistController::class, 'store'])->name('checklists.store');
    Route::get('/checklists/{checklist}', [ChecklistController::class, 'show']);
    Route::get('/checklists/{checklist}/edit', [ChecklistController::class, 'edit']);
    Route::patch('/checklists/{checklist}', [ChecklistController::class, 'update']);
    Route::delete('/checklists/{checklist}', [ChecklistController::class, 'destroy']);

    //Tasks
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

});

