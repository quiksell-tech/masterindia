<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\MiCompany;
use App\Models\Admin\MiCompanyAddress;
use App\Models\Admin\MiPincodeMaster;
use App\Models\Admin\MiParty;

class CompanyAddressController extends Controller
{
    public function index()
    {
        $addresses = MiCompanyAddress::with('company')
            ->orderByDesc('address_id')
            ->paginate(10);

        return view('company_addresses.index', compact('addresses'));
    }

    public function create()
    {
        $companies = MiCompany::where('is_active', 'Y')->get();

        return view('company_addresses.create', compact('companies'));
    }

    public function fetchPincode($pincode)
    {
        $data = MiPincodeMaster::where('pincode', $pincode)
            ->where('is_active', 'Y')
            ->first();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id'   => 'required',
            'address_type' => 'required|in:office,shipping',
            'address_line' => 'required',
            'pincode'      => 'required',
            'party_id'      => 'required',
        ]);

        $pincode = MiPincodeMaster::where('pincode', $request->pincode)->first();

        if (!$pincode) {
            return back()->withErrors(['pincode' => 'Invalid pincode']);
        }

        MiCompanyAddress::create([
            'company_id'   => $request->company_id,
            'address_type' => $request->address_type,
            'address_line' => $request->address_line,
            'city'         => $pincode->city_name,
            'state'        => $pincode->state_name,
            'state_code'   => $pincode->state_code,
            'pincode'      => $pincode->pincode,
            'pincode_id'   => $pincode->pincode_id,
            'party_id'   => $request->party_id,
            'is_active'    => 'Y',
        ]);

        return redirect()->route('company-addresses.index')
            ->with('success', 'Address added successfully');
    }
    public function edit($id)
    {
        $address = MiCompanyAddress::findOrFail($id);

        $companies = MiCompany::where('is_active', 'Y')->get();

        $parties = MiParty::where('company_id', $address->company_id)
            ->select('party_id', 'party_name as name')
            ->orderBy('party_name')
            ->get();

        return view('company_addresses.edit', compact(
            'address',
            'companies',
            'parties'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'company_id'   => 'required',
            'address_type' => 'required|in:office,shipping',
            'address_line' => 'required',
            'pincode'      => 'required',
            'party_id'      => 'required',
        ]);

        $address = MiCompanyAddress::findOrFail($id);

        $pincode = MiPincodeMaster::where('pincode', $request->pincode)->first();
        if (!$pincode) {
            return back()->withErrors(['pincode' => 'Invalid pincode']);
        }

        $address->update([
            'company_id'   => $request->company_id,
            'address_type' => $request->address_type,
            'address_line' => $request->address_line,
            'party_id' => $request->party_id,
            'city'         => $pincode->city_name,
            'state'        => $pincode->state_name,
            'state_code'   => $pincode->state_code,
            'pincode'      => $pincode->pincode,
            'pincode_id'   => $pincode->pincode_id,
        ]);

        return redirect()->route('company-addresses.index')
            ->with('success', 'Address updated successfully');
    }
    public function getParty(Request $request)
    {
        $companyId = $request->get('company_id');

        if (empty($companyId)) {
            return response()->json([]);
        }

        $parties = MiParty::where('company_id', $companyId)
            ->select('party_id', 'party_trade_name as name')
            ->orderBy('name')
            ->get();

        return response()->json($parties);
    }
}
