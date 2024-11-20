<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    // Show all current bookings for the logged-in user
    public function index()
    {
        return auth()->user()->bookings()->with('product')->get();
    }

    // Show a specific booking for the logged-in user
    public function show($id)
    {
        $booking = auth()->user()->bookings()->find($id);
        if (!$booking) {
            return response()->json([
                'error' => 'Booking not found.'
            ], 404);
        }
        return response()->json($booking);
    }

    // Store a new booking
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'start_booking_date' => 'required|date',
            'end_booking_date' => 'required|date|after_or_equal:start_booking_date',
            'product_id' => 'required|exists:products,id',
        ]);
        
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        // If validation passes, get the validated data
        $validated = $validator->validated();

        // Check if the user exists based on the provided email
        $user = User::where('email', $validated['customer_email'])->first();

        // If user does not exist, create a guest user
        if (!$user) {
            $user = User::create([
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'password' => bcrypt(Str::random(16)), // Generate a random password for the guest user
            ]);
        }

        // Start a transaction for concurrency control
        DB::beginTransaction();

        try {
            // Fetch product and check for availability
            $product = Product::find($request->product_id);
            if (!$product) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Product not found.'
                ], 404);
            }

            if ($product->stock <= 0) {
                DB::rollBack();
                return response()->json(['error' => 'Product is out of stock.'], 400);
            }

            // Check for double booking within the same date range
            $existingBooking = Booking::where('product_id', $request->product_id)
                ->where('user_id', $user && $user->id ? $user->id : auth()->id())
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_booking_date', [$request->start_booking_date, $request->end_booking_date])
                        ->orWhereBetween('end_booking_date', [$request->start_booking_date, $request->end_booking_date])
                        ->orWhere(function ($query) use ($request) {
                            $query->where('start_booking_date', '<=', $request->start_booking_date)
                                    ->where('end_booking_date', '>=', $request->end_booking_date);
                        });
                })
                ->exists();

            if ($existingBooking) {
                DB::rollBack();
                return response()->json(['error' => 'Product is already booked for the selected date range.'], 400);
            }

            // Optimistic Locking: Increase the version column of the product to prevent race conditions
            $product->lockForUpdate();  // Pessimistic Locking

            // Decrement the stock as the product is booked
            $product->stock -= 1;
            $product->save();

            // Create the booking and associate it with the user (either registered or guest)
            $booking = Booking::create([
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'start_booking_date' => $validated['start_booking_date'],
                'end_booking_date' => $validated['end_booking_date'],
                'product_id' => $validated['product_id'],
                'user_id' => $user && $user->id ? $user->id : auth()->id(),
            ]);
            // Commit the transaction
            DB::commit();

            return response()->json($booking, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            // Return the actual error message from the exception in the response
            return response()->json([
                'error' => 'Something went wrong, please try again later.',
                'message' => $e->getMessage(),  // Return the exception message
                'stack' => $e->getTraceAsString(), // Return the stack trace for debugging
            ], 500);
        }
    }

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
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'error' => 'Booking not found.'
            ], 404);
        }

        // Ensure the user is trying to update their own booking
        if ($booking->user_id != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Start a transaction for concurrency control
        DB::beginTransaction();

        try {
            // Check if product availability and double booking logic
            $product = Product::find($booking->product_id);
            if (!$product) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Product not found.'
                ], 404);
            }

            if ($product->stock <= 0) {
                DB::rollBack();
                return response()->json(['error' => 'Product is out of stock.'], 400);
            }

            // Check for double booking after updating the dates
            $existingBooking = Booking::where('product_id', $request->product_id)
                ->where('user_id', auth()->id())
                ->where(function ($query) use ($request) {
                    $query->whereBetween('start_booking_date', [$request->start_booking_date, $request->end_booking_date])
                        ->orWhereBetween('end_booking_date', [$request->start_booking_date, $request->end_booking_date])
                        ->orWhere(function ($query) use ($request) {
                            $query->where('start_booking_date', '<=', $request->start_booking_date)
                                    ->where('end_booking_date', '>=', $request->end_booking_date);
                        });
                })
                ->exists();

            if ($existingBooking) {
                DB::rollBack();
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
                'customer_name' => $request->customer_name,
            ]);

            // Commit the transaction
            DB::commit();

            return response()->json($booking);

        } catch (\Exception $e) {
            DB::rollBack();
            // Return the actual error message from the exception in the response
            return response()->json([
                'error' => 'Something went wrong, please try again later.',
                'message' => $e->getMessage(),  // Return the exception message
                'stack' => $e->getTraceAsString(), // Return the stack trace for debugging
            ], 500);
        }
    }


    // Delete a booking
    public function destroy($id)
    {
        $booking = Booking::find($id);

            if (!$booking) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Booking not found.'
                ], 404);
            }

        // Ensure the user is trying to delete their own booking
        if ($booking->user_id != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }
}