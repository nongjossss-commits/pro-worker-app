<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Employer;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'addressable_id' => 'required',
            'addressable_type' => 'required',
            'type' => 'required|string|in:registered,workplace',
            'addrNo' => 'nullable|string|max:255',
            'addrMoo' => 'nullable|string|max:255',
            'addrSoi' => 'nullable|string|max:255',
            'addrRoad' => 'nullable|string|max:255',
            'addrProvince' => 'nullable|string|max:255',
            'addrDistrict' => 'nullable|string|max:255',
            'addrSubDistrict' => 'nullable|string|max:255',
            'addrZipCode' => 'nullable|string|max:255',
        ]);

        $modelClass = 'App\\Models\\' . ucfirst($request->addressable_type);
        $parent = $modelClass::findOrFail($request->addressable_id);

        $address = $parent->addresses()->create($request->except(['addressable_id', 'addressable_type']));

        return response()->json($address);
    }

    public function edit(Address $address)
    {
        return response()->json($address);
    }

    public function update(Request $request, Address $address)
    {
        $request->validate([
            'type' => 'required|string|in:registered,workplace',
            'addrNo' => 'nullable|string|max:255',
            'addrMoo' => 'nullable|string|max:255',
            'addrSoi' => 'nullable|string|max:255',
            'addrRoad' => 'nullable|string|max:255',
            'addrProvince' => 'nullable|string|max:255',
            'addrDistrict' => 'nullable|string|max:255',
            'addrSubDistrict' => 'nullable|string|max:255',
            'addrZipCode' => 'nullable|string|max:255',
        ]);

        $address->update($request->all());

        return response()->json($address);
    }

    public function destroy(Address $address)
    {
        $address->delete();

        return response()->json(['success' => 'Address deleted successfully.']);
    }
}
