<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validated = Validator::make($request->all(), [
            'room_name' => 'required|string',
            'room_capacity' => 'required|string',
            'price' => 'required|integer',
        ])->validate();

        $product = Product::create($validated);
        return response()->json($product, 201);
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        return Product::findOrFail($id);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'room_name' => 'string',
            'room_capacity' => 'string',
            'price' => 'integer',
        ])->validate();

        $product = Product::findOrFail($id);
        $product->update($validated);

        return response()->json($product);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy($id)
    {
  
    }
}