<?php

use Illuminate\Support\Facades\Route;

/*Route::get('/', function () {
    return view('welcome');
   // return redirect('/', '/admin/login');

});*/

//Route::redirect('/', '/admin/login');


//added

/*Route::get('/login', function () {
    return response()->json(['error' => 'Unauthorized'], 401);
})->name('login');*/
Route::get('/user-activity-details/{student_id_hash}', \App\Filament\Pages\UserActivityDetails::class)
    ->name('filament.pages.user-activity-details')
    ->middleware(['auth']);
