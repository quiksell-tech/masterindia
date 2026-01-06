<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MiCompany;
use App\Models\Admin\MiParty;
use Illuminate\Http\Request;

class MiPartyController extends Controller
{
    public function index()
    {
        $parties = MiParty::with('company')
            ->orderBy('party_id', 'asc')
            ->get();

        return view('party.index', compact('parties'));
    }

    public function create()
    {
        $companies = MiCompany::orderBy('name')->get();
        return view('party.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id'       => 'required',
            'party_trade_name'  => ['required', 'string', 'max:255'],
            'party_legal_name'  => ['required', 'string', 'max:255'],
            'contact_name'      => ['required', 'string', 'max:255'],
            'name'              => ['required', 'string', 'max:50'],
            'phone'             => ['required', 'digits:10'],
            'email'             => ['required', 'email', 'max:255'],
            'party_gstn'        => ['required', 'string', 'size:15'],
            'is_active'         => ['required', 'in:Y,N'],
        ]);

        MiParty::create($request->only([
            'company_id',
            'party_gstn',
            'contact_name',
            'party_trade_name',
            'party_legal_name',
            'name',
            'phone',
            'email',
            'is_active'
        ]));

        return redirect()->route('party.index')->with('success', 'Party created successfully');
    }

    public function edit($id)
    {
        $party     = MiParty::findOrFail($id);
        $companies = MiCompany::orderBy('name')->get();

        return view('party.edit', compact('party', 'companies'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'company_id'       => 'required',
            'party_trade_name' => 'required',
            'party_legal_name' => 'required',
            'name'              => ['required', 'string', 'max:50'],
            'phone'             => ['required', 'digits:10'],
            'email'             => ['required', 'email', 'max:255'],
            'contact_name' => 'required',
            'is_active'        => 'required|in:Y,N',
        ]);
          if($id==1)
          {
              return redirect()->route('party.index')->with('success', 'Party updated successfully');
          }
        $party = MiParty::findOrFail($id);
        $party->update($request->only([
            'company_id',
            'party_gstn',
            'party_trade_name',
            'contact_name',
            'party_legal_name',
            'email',
            'phone',
            'name',
            'is_active'
        ]));

        return redirect()->route('party.index')->with('success', 'Party updated successfully');
    }

}
