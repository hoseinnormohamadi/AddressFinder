<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\V1\User as UserResource;
class UserController extends Controller
{
    public function UserInfo(){
        return auth()->user();
    }
    public function login(Request $request)
    {
        $input = $request->only('email', 'password');
        $token = null;
        if (!$token = JWTAuth::attempt($input)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Email or Password',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }
    public function register(Request $request)
    {
        $validData = $this->validate($request, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user = User::create([
            'name' => $validData['name'],
            'email' => $validData['email'],
            'password' => Hash::make($validData['password']),
            'Api_Token' => Str::random(100)
        ]);
        auth()->login($user);
        $token = $this->Create_Token();
        return new UserResource($user , $token);
    }
    public function Create_Token()
    {
        auth()->user()->tokens()->delete();
        return auth()->user()->createToken('Api_Token')->accessToken;

    }
}
