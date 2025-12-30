<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Admin\MiCompany;
use Illuminate\Http\Request;

class MiCompanyController extends Controller
{
    public function index()
    {
        $companies = MiCompany::orderBy('company_id', 'desc')->get();
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required',
            'legal_name' => 'required',
            'is_active'  => 'required|in:Y,N',
        ]);

        MiCompany::create($request->only('name','legal_name','is_active'));

        return redirect()->route('companies.index')
            ->with('success', 'Company created successfully');
    }

    public function edit($id)
    {
        $company = MiCompany::findOrFail($id);
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required',
            'legal_name' => 'required',
            'is_active'  => 'required|in:Y,N',
        ]);

        $company = MiCompany::findOrFail($id);
        $company->update($request->only('name','legal_name','is_active'));

        return redirect()->route('companies.index')
            ->with('success', 'Company updated successfully');
    }
}
