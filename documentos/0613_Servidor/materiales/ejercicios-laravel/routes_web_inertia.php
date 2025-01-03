<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProyectosController;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\ReconocimientoController;
use App\Http\Controllers\CurriculoController;
use App\Http\Controllers\CicloController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\TallerController;

use Illuminate\Foundation\Application;
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

Route::get('/', function () {
    return view('home');
})->name('home');

/*
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');
*/

Route::prefix('proyectos')->group(function () {
    Route::get('/', [ProyectosController::class, 'getIndex'])->name('proyectos');

    Route::get('/show/{id}', [ProyectosController::class, 'getShow'])->where('id', '[0-9]+');

    Route::put('/editcalificacion/{id}', [ProyectosController::class, 'editCalificacion'])->where('id', '[0-9]+')
    ->middleware('auth');

    Route::get('/create', [ProyectosController::class, 'getCreate'])
    ->middleware('auth');

    Route::get('/edit/{id}', [ProyectosController::class, 'getEdit'])->where('id', '[0-9]+')
    ->middleware('auth');

    Route::put('/edit/{id}', [ProyectosController::class, 'putEdit'])->where('id', '[0-9]+')
    ->middleware('auth');

    Route::post('/', [ProyectosController::class, 'store']);
});

Route::prefix('reconocimientos')->group(function () {

    Route::get('/', [ReconocimientoController::class, 'getIndex'])->name('reconocimientos');

    Route::get('/show/{id}', [ReconocimientoController::class, 'getShow'])->where('id', '[0-9]+');

    Route::get('/create', [ReconocimientoController::class, 'getCreate'])->middleware('auth');

    Route::put('/edit/{id}', [ReconocimientoController::class, 'putEdit'])->where('id', '[0-9]+');

    Route::get('/edit/{id}', [ReconocimientoController::class, 'getEdit'])->where('id', '[0-9]+')->middleware('auth');

    Route::post('/', [ReconocimientoController::class, 'store']);

    Route::put('/show/{id}', [ReconocimientoController::class, 'putShow'])->where('id', '[0-9]+')->middleware('auth');

    Route::put('/show/{id}', [ReconocimientoController::class, 'valida'])->where('id', '[0-9]+')->middleware('auth');
});

Route::prefix('users')->group(function () {

    Route::get('/', [UserController::class, 'getIndex'])->name('usuarios');

    Route::get('/show/{id}', [UserController::class, 'getShow'])->where('id', '[0-9]+');

    Route::get('/create', [UserController::class, 'getCreate'])->middleware('auth');

    Route::put('/edit/{id}', [UserController::class, 'putEdit'])->name('user.putEdit')->where('id', '[0-9]+');

    Route::get('/edit/{id}', [UserController::class, 'getEdit'])->where('id', '[0-9]+')->middleware('auth');
});

Route::prefix('actividades')->group(function () {

    Route::get('/', [ActividadController::class, 'getIndex'])->name('actividades');

    Route::get('/show/{id}', [ActividadController::class, 'getShow'])->where('id', '[0-9]+');

    Route::get('/create', [ActividadController::class, 'getCreate'])->middleware('auth');

    Route::get('/edit/{id}', [ActividadController::class, 'getEdit'])->where('id', '[0-9]+')->middleware('auth');

    Route::put('/edit/{id}', [ActividadController::class, 'putEdit'])->where('id', '[0-9]+');

    Route::post('/', [ActividadController::class, 'store']);

});

Route::prefix('curriculos')->group(function () {

    Route::get('/', [CurriculoController::class, 'getIndex'])->name('curriculos');

    Route::get('/show/{id}', [CurriculoController::class, 'getShow'])->where('id', '[0-9]+');

    Route::get('/create', [CurriculoController::class, 'getCreate'])->middleware('auth');

    Route::get('/edit/{id}', [CurriculoController::class, 'getEdit'])->where('id', '[0-9]+')->middleware('auth');

    Route::put('/edit/{id}', [CurriculoController::class, 'putEdit'])->where('id', '[0-9]+');

    Route::post('/', [CurriculoController::class, 'store']);

});

Route::prefix('ciclos')->group(function () {

    Route::get('/', [CicloController::class, 'getIndex'])->name('ciclos');

    Route::get('/show/{id}', [CicloController::class, 'getShow'])->where('id', '[0-9]+');

    Route::get('/create', [CicloController::class, 'getCreate'])->middleware('auth');

    Route::get('/edit/{id}', [CicloController::class, 'getEdit'])->where('id', '[0-9]+')->middleware('auth');

    Route::put('/edit/{id}', [CicloController::class, 'putEdit'])->where('id', '[0-9]+');

    Route::post('/', [CicloController::class, 'store']);

});

Route::prefix('docentes')->group(function () {

    Route::get('/', [DocenteController::class, 'getIndex'])->name('docentes');

    Route::get('/show/{id}', [DocenteController::class, 'getShow'])->where('id', '[0-9]+');

    Route::get('/create', [DocenteController::class, 'getCreate'])->middleware('auth');

    Route::get('/edit/{id}', [DocenteController::class, 'getEdit'])->where('id', '[0-9]+')->middleware('auth');

    Route::put('/edit/{id}', [DocenteController::class, 'putEdit'])->where('id', '[0-9]+');
});

Route::get('/talleres', [TallerController::class, 'getIndex']);

Route::get('perfil/{id?}', function ($id = null) {
    if ($id == null) {
        return "Visualizar el currículo propio";
    } else {
        return "Visualizar el currículo de " . $id;
    }
})->where('id', '[0-9]+')->name('perfil');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
