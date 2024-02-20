<?php

use App\Http\Controllers\GraphQlController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    $link = "gid://shopify/Product/8168406221036";
    $parts = explode('/', $link);
    $id = end($parts);
    dd($id);
    return view('welcome');
});
