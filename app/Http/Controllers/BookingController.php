<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $user = Auth::user();
        return $user->bookings;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string',
            'booking_date' => 'required|date',
            'room_number' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = Auth::user();
        $validated = $validator->validated();
        $validated['user_id'] = $user->id;

        $booking = Booking::create($validated);
        return response()->json($booking, 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $booking = $user->bookings()->findOrFail($id);
        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'string',
            'booking_date' => 'date',
            'room_number' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = Auth::user();
        $booking = $user->bookings()->findOrFail($id);
        $booking->update($validator->validated());

        return response()->json($booking);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $booking = $user->bookings()->findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }
}