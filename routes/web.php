<?php

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
    return view('welcome');
});


Route::resource('/contatos', 'Backend\ContatoController');

Route::get('/contato', 'Frontend\ContatoController@index');
Route::post('/contato-enviar', 'Frontend\ContatoController@contatoEnvia')->name('save.contato');


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
