<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        // Validate the registration data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Create a new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Generate the JWT token
        $token = JWTAuth::fromUser($user);

        // Return the token as response
        return response()->json(['token' => $token], 201);
    }

    // Log in an existing user
    public function login(Request $request)
    {
        // Validate login credentials
        $credentials = $request->only('email', 'password');

        if ($token = JWTAuth::attempt($credentials)) {
            // If successful, return the JWT token
            return response()->json(['token' => $token]);
        }

        // If the credentials are invalid, return an error
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Get the authenticated user
    public function user(Request $request)
    {
        // Retrieve the authenticated user from the JWT token
        $user = Auth::user();

        return response()->json($user);
    }

    // Log out the user (invalidate the token)
    public function logout()
    {
        // Invalidate the current user's token
        Auth::logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
}

