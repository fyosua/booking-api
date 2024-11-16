<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookingController extends Controller
{
    // Show all current bookings for the logged-in user
    public function index()
    {
        return auth()->user()->bookings()->get();
    }

    // Show a specific booking for the logged-in user
    public function show($id)
    {
        $booking = auth()->user()->bookings()->findOrFail($id);
        return response()->json($booking);
    }

    // Store a new booking
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'start_booking_date' => 'required|date',
            'end_booking_date' => 'required|date|after_or_equal:start_booking_date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Start a transaction for concurrency control
        DB::beginTransaction();

        try {
            // Fetch product and check for availability
            $product = Product::findOrFail($request->product_id);

            if ($product->stock <= 0) {
                return response()->json(['error' => 'Product is out of stock.'], 400);
            }

            // Check for double booking within the same date range
            $existingBooking = Booking::where('product_id', $request->product_id)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_booking_date', [$request->start_booking_date, $request->end_booking_date])
                          ->orWhereBetween('end_booking_date', [$request->start_booking_date, $request->end_booking_date]);
                })
                ->exists();

            if ($existingBooking) {
                return response()->json(['error' => 'Product is already booked for the selected date range.'], 400);
            }

            // Optimistic Locking: Increase the version column of the product to prevent race conditions
            $product->lockForUpdate();  // Pessimistic Locking

            // Decrement the stock as the product is booked
            $product->stock -= 1;
            $product->save();

            // Create the booking
            $booking = Booking::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'start_booking_date' => $request->start_booking_date,
                'end_booking_date' => $request->end_booking_date,
            ]);

            // Commit the transaction
            DB::commit();

            return response()->json($booking, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong, please try again later.'], 500);
        }
    }

    // Update a booking
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'start_booking_date' => 'required|date',
            'end_booking_date' => 'required|date|after_or_equal:start_booking_date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Fetch and check the booking
        $booking = Booking::findOrFail($id);

        // Ensure the user is trying to update their own booking
        if ($booking->user_id != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Start a transaction for concurrency control
        DB::beginTransaction();

        try {
            // Check if product availability and double booking logic
            $product = Product::findOrFail($booking->product_id);

            if ($product->stock <= 0) {
                return response()->json(['error' => 'Product is out of stock.'], 400);
            }

            // Check for double booking after updating the dates
            $existingBooking = Booking::where('product_id', $booking->product_id)
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_booking_date', [$request->start_booking_date, $request->end_booking_date])
                          ->orWhereBetween('end_booking_date', [$request->start_booking_date, $request->end_booking_date]);
                })
                ->where('id', '!=', $booking->id) // Exclude the current booking itself
                ->exists();

            if ($existingBooking) {
                return response()->json(['error' => 'Product is already booked for the selected date range.'], 400);
            }

            // Pessimistic Locking: Lock the product to prevent race conditions
            $product->lockForUpdate();  // Pessimistic Locking

            // Decrement the stock as the product is booked
            $product->stock -= 1;
            $product->save();

            // Update the booking
            $booking->update([
                'start_booking_date' => $request->start_booking_date,
                'end_booking_date' => $request->end_booking_date,
            ]);

            // Commit the transaction
            DB::commit();

            return response()->json($booking);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong, please try again later.'], 500);
        }
    }

    // Delete a booking
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);

        // Ensure the user is trying to delete their own booking
        if ($booking->user_id != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }
}