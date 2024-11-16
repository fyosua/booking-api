<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * Display a listing of the bookings for the logged-in user.
     */
    public function index()
    {
        $user = Auth::user();
        $bookings = $user->bookings;
        
        return response()->json($bookings);
    }

    /**
     * Store a newly created booking in storage.
     */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'start_booking_date' => 'required|date',
            'end_booking_date' => 'required|date|after_or_equal:start_booking_date',
            'product_id' => 'required|exists:products,id',
        ])->validate();

        $booking = new Booking($validated);
        $booking->user_id = Auth::id();
        $booking->save();

        return response()->json($booking, 201);
    }

    /**
     * Display the specified booking for the logged-in user.
     */
    public function show($id)
    {
        $user = Auth::user();
        $booking = Booking::where('user_id', $user->id)->findOrFail($id);

        return response()->json($booking);
    }

    /**
     * Update the specified booking in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'start_booking_date' => 'date',
            'end_booking_date' => 'date|after_or_equal:start_booking_date',
            'product_id' => 'exists:products,id',
        ])->validate();

        $user = Auth::user();
        $booking = Booking::where('user_id', $user->id)->findOrFail($id);
        $booking->update($validated);

        return response()->json($booking);
    }

    /**
     * Remove the specified booking from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $booking = Booking::where('user_id', $user->id)->findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }
}