<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;

class ProductionDocumentController extends Controller
{
    public function show(Request $request, $id, $type)
    {
        $production = ProductionOrder::with(['employer', 'items.employee', 'items'])->findOrFail($id);

        // Basic Validation of type
        if (!in_array($type, ['quotation', 'invoice', 'receipt', 'credit_note'])) {
            abort(404);
        }

        // Get Company Profile
        $profileId = $request->query('profile_id');
        $companyProfile = $profileId ? CompanyProfile::find($profileId) : CompanyProfile::first();

        // Prepare data for the view
        $data = [
            'production' => $production,
            'company' => $companyProfile,
            'type' => $type,
            'date' => now(),
        ];

        return view('production.documents.' . $type, $data);
    }
}
