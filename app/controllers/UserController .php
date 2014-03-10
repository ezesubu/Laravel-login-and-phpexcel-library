<?php

class UserController extends BaseController {

    /**
     * Show the profile for the given user.
     */

    public function showProfile()
    {
		$user = User::all();

        return View::make('users', array('users' => $user));
    }

    public function uploadCSV($file_csv){
    	try{
    		$list_users=Excel::load($file_csv)->setDelimiter(';')->toArray();
    	}
    		catch(PDOException $exception) {
    		return Response::make('Database error! ' . $exception->getCode());
		}
		foreach ($list_users as  $user) { 
			var_dump($user);		
		}
   
    }

}
