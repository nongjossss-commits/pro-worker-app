<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Employer; // Assuming Employer model is used
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddressController extends Controller
{
    /**
     * Get Thai address data from JSON file.
     * THIS IS THE CRITICAL MISSING FUNCTION.
     */
    public function getThaiAddressData()
    {
        if (!Storage::disk('local')->exists('thai-address-data.json')) {
            return response()->json(['error' => 'Address data file not found in storage/app/.'], 404);
        }

        $json = Storage::disk('local')->get('thai-address-data.json');
        $data = json_decode($json, true);

        return response()->json($data);
    }

    /**
     * Store a newly created address in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'addressable_id' => 'required|integer',
            'addressable_type' => 'required|string',
            'type' => 'required|string|in:registered,workplace',
            // Add other address fields validation as needed
        ]);

        $modelClass = 'App\\Models\\' . ucfirst($validated['addressable_type']);

        try {
            $parent = $modelClass::findOrFail($validated['addressable_id']);
            $address = $parent->addresses()->create($request->all());
            return response()->json(['success' => true, 'address' => $address], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Parent model not found.'], 404);
        }
    }

    /**
     * Show the form for editing the specified address.
     */
    public function edit(Address $address)
    {
        return response()->json($address);
    }

    /**
     * Update the specified address in storage.
     */
    public function update(Request $request, Address $address)
    {
        $validated = $request->validate([
            // Add validation rules for update as needed
        ]);

        $address->update($request->all());
        return response()->json(['success' => true, 'address' => $address]);
    }

    /**
     * Remove the specified address from storage.
     */
    public function destroy(Address $address)
    {
        $address->delete();
        return response()->json(['success' => true, 'message' => 'Address deleted successfully.']);
    }
}