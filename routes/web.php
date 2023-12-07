<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommercetoolsExtensionsController;

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

// Commercetools API extensions request handler
Route::post('/commercetools/api/extensions', [
    CommercetoolsExtensionsController::class, 'handleRequest'
])->middleware('commercetools.extensions.guard');

// Return a 403 error (forbidden) for all routes, except for Commercetools API extensions.
Route::fallback(function () {
    abort(403);
});
