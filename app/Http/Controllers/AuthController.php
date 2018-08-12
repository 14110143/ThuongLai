<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Http\Exception\HttpResponseException;
use Tymon\JWTAuth\Exceptions\JWTException;

use Hash;
use JWTAuth;
use JWTAuthException;
use Validator;
use Session;
use Cache;

class AuthController extends Controller
{
	private $user;

	public function __construct(User $user) {
		$this->user = $user;
	}

    public function login(Request $request) {
    	$this->validateLogin($request);

        $credentials = $this->getCredentials($request);
        $token = null;

        try {
        	// attempt to verify the credentials and create a token for the user
           	if ( !$token = JWTAuth::attempt($credentials)) {
            	return response()->json([
            		'errorMessage' => 'invalid_credentials',
            		'statusCode' => IlluminateResponse::HTTP_UNAUTHORIZED],
            		IlluminateResponse::HTTP_UNAUTHORIZED);
           	}
        } catch (JWTAuthException $e) {
        	// something went wrong whilst attempting to encode the token
        	return response()->json(['errorMessage' => 'could_not_create_token', 'statusCode' => IlluminateResponse::HTTP_INTERNAL_SERVER_ERROR], IlluminateResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // normalize email from request
        $email = strtolower(trim($request->input('email')));

        // get user information
        if (!Cache::tags(['auth'])->has($email)) {
            $user = $this->user
                ->where('email', 'like', $email)
                ->first();

            if (empty($user))
                return response()->json(['errorMessage' => 'invalid_credentials', 'statusCode' => IlluminateResponse::HTTP_UNAUTHORIZED], IlluminateResponse::HTTP_UNAUTHORIZED);

            $user = $user->toArray();

            Cache::tags(['auth'])->put($email, ['data' => $user], config('cache.time'));
        } else {
            $user = Cache::tags(['auth'])->get($email);
            $user = $user['data'];
        }

        Session::put([
            TOKEN_CORE_SERVICE => $token,
            'email' => strtolower(trim($request->input('email')))
        ]);

        // all good so return the token and uid
        return response()->json([
                'token' => $token,
                'uid' => (string)$user['id'],
                'statusCode' => IlluminateResponse::HTTP_OK,
                'detail' => array_except($user, ['updated_at', 'created_at']
            )
        ]);
    }

    public function register(Request $request) {

    	$this->validateRegister($request);

        $user = $this->user->create([
          'name' => $request->get('name'),
          'email' => $request->get('email'),
          'password' => Hash::make($request->get('password'))
        ]);
        
        if ($user) {
            Session::flash('successMsgs', json_encode(['The user has just been registered successfully.', 'Please login with your information.']));
            return redirect()->route('login');
        } else {
            Session::flash('errorMsgs', json_encode(['The user can not be registered']));
            return redirect()->route('register');
        }

        

        // return response()->json([
        //     'status' => 200,
        //     'message' => 'User created successfully',
        //     'data' => $user
        // ]);
    }

    public function getUserInfo(Request $request) {
        $user = JWTAuth::toUser($request->token);
        return response()->json(['result' => $user]);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    protected function getCredentials(Request $request)
    {
        return $request->only('email', 'password');
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
    	try {
            $this->validate($request, [
                'email' => 'required|email|max:255', 'password' => 'required',
            ]);
        } catch (HttpResponseException $e) {
            return response()->json(['errorMessage' => 'Invalid auth', 'statusCode' => IlluminateResponse::HTTP_BAD_REQUEST],
                IlluminateResponse::HTTP_BAD_REQUEST,
                $headers = []
            );
        }
    }

    protected function validateRegister(Request $request) {
    	$result = Validator::make($request->only(['name', 'email', 'password', 'password_confirmation']), [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email,_id',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($result->fails()) {
            return response()->json(['errors' => $result->errors()->all(), 'errorMessage' => 'Fail Validation']);
        }

        return response()->json('OK');
    }

}
