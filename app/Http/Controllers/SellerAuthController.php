<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SellerUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SellerAuthController extends Controller
{
    // Register a new seller user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:seller_users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $sellerUser = SellerUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // Generate the JWT token using the specified guard
        $token = Auth::guard('seller')->login($sellerUser);

        return response()->json(['token' => $token], 201);
    }

    // Log in an existing seller user
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($token = Auth::guard('seller')->attempt($credentials)) {
            return response()->json(['token' => $token]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Get the authenticated seller user
    public function user(Request $request)
    {
        $sellerUser = Auth::guard('seller')->user();

        return response()->json($sellerUser);
    }

    // Log out the seller user (invalidate the token)
    public function logout()
    {
        Auth::guard('seller')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
