<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', array('as' => 'home', function () {
    return View::make('home');
}));

Route::get('login', array('as' => 'login', function () {
    return View::make('login');
}));

Route::get('upload', array('as' => 'upload','before' => 'guest', function () {
    return View::make('upload');
}));


Route::post('upload', array('as' => 'upload', 'before' => 'guest', function () {

 
     $validator = Validator::make(
        array('trackfile' => Input::file('csv')), 
        array('trackfile' => 'mimes:csv')
    );

    if($validator->fails()){
         return Redirect::route('upload')
            ->with('flash_error', 'doesn\'t works because mime type is '.Input::file('csv')->getMimeType())
            ->withInput();
    }else{
        // $userOrders = UserController::uploadCSV("hola");
        $userOrders= App::make('UserController')->uploadCSV(Input::file('csv'));
    }


}));



Route::post('login', function () {
	
        $user = array(
            'username' => Input::get('username'),
            'password' => Input::get('password')
        );
        if (Auth::attempt($user)) {	
        	
            return Redirect::route('home')
                ->with('flash_notice', 'You are successfully logged in.');
            
        }
        
        // authentication failure! lets go back to the login page
        return Redirect::route('login')
            ->with('flash_error', 'Your username/password combination was incorrect.')
            ->withInput();
});

Route::get('logout', array('as' => 'logout', function () {
    Auth::logout();

    return Redirect::route('home')
        ->with('flash_notice', 'You are successfully logged out.');
}))->before('auth');

Route::get('profile', array('as' => 'profile', function () { }));

