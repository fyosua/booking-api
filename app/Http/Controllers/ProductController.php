<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PhotoAlbum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index()
    {
        return Product::all();
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validated = Validator::make($request->all(), [
            'room_name' => 'required|string',
            'room_capacity' => 'required|integer',
            'price' => 'required|integer',
            'stock' => 'required|integer',
            'description' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        // If validation fails, return error response
        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Create the product
            $product = Product::create($validated->validated());

            // Handle the uploaded photos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $filename = $photo->getClientOriginalName();
                    $path = $photo->storeAs('product_photos', $filename, 'product_photos');

                    // Save each photo to the photo_album table
                    PhotoAlbum::create([
                        'product_id' => $product->id,
                        'file_path' => $path, // Save the file path
                    ]);
                }
            }

            DB::commit();
            return response()->json($product->load('photos'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to save product or photos.'], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        $product = Product::find($id);

            if (!$product) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Product not found.'
                ], 404);
            }
        return $product;
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $validated = Validator::make($request->all(), [
            'room_name' => 'nullable|string',
            'room_capacity' => 'nullable|integer',
            'price' => 'nullable|integer',
            'stock' => 'nullable|integer',
            'description' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        // If validation fails, return error response
        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $product = Product::find($id);

            if (!$product) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Product not found.'
                ], 404);
            }

            // Update the product details
            $product->update($validated->validated());

            // If photos are uploaded, handle them
            if ($request->hasFile('photos')) {
                // Delete existing photos associated with this product
                $existingPhotos = PhotoAlbum::where('product_id', $product->id)->get();
                foreach ($existingPhotos as $photo) {
                    // Delete the photo file from storage
                    Storage::disk('product_photos')->delete($photo->file_path);
                    // Delete the record from photo_albums table
                    $photo->delete();
                }
                // Upload the new photos and save them in the photo_album table
                foreach ($request->file('photos') as $photo) {
                    // Generate the filename using the original name
                    $filename = $photo->getClientOriginalName();
                    // Store the photo in the product_photos folder
                    $path = $photo->storeAs('product_photos', $filename, 'product_photos');
                    // Create a new record in the photo_album table
                    PhotoAlbum::create([
                        'product_id' => $product->id,
                        'file_path' => $path,
                    ]);
                }
            }

            DB::commit();
            return response()->json($product->load('photos'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update product or photos.'], 500);
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return response()->json(['error' => 'Product not found.'], 404);
            }

            // Delete associated photos
            $photos = PhotoAlbum::where('product_id', $product->id)->get();
            foreach ($photos as $photo) {
                // Delete the photo file from storage
                Storage::disk('product_photos')->delete($photo->file_path);
                // Delete the photo record from the photo_albums table
                $photo->delete();
            }
            // Delete the product from the products table
            $product->delete();

            // Return success response
            return response()->json(['message' => 'Product and associated photos deleted successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete product or photos.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}