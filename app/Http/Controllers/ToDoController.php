<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Todo;
use Auth;

class ToDoController extends Controller
{
    public function list(Request $request) {
    	$todos = Todo::get();
    	$user = $request->input('api_token');
    	dd($user);
    	return response()->json([
    		'todo' => $todos
    	]);
    }
}
