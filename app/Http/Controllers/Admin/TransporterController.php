<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\MiTransporter;
use Illuminate\Http\Request;

class TransporterController extends Controller
{
    public function index()
    {
        $transporters = MiTransporter::orderBy('name', 'desc')->paginate(10);
        return view('transporters.index', compact('transporters'));
    }

    public function create()
    {
        return view('transporters.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'transporter_gstn' => 'nullable|string|max:20',
            'is_active' => 'required|in:Y,N',
        ]);

        MiTransporter::create($request->only([
            'name',
            'transporter_gstn',
            'is_active'
        ]));

        return redirect()
            ->route('transporters.index')
            ->with('success', 'Transporter added successfully');
    }

    public function edit($id)
    {
        $transporter = MiTransporter::findOrFail($id);
        return view('transporters.edit', compact('transporter'));
    }

    public function update(Request $request, $id)
    {
        if($id==1 || $id==2){
            // used as fixed transporter
            return redirect()
                ->route('transporters.index')
                ->with('success', 'Transporter updated successfully');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'transporter_gstn' => 'nullable|string|max:20',
            'is_active' => 'required|in:Y,N',
        ]);

        $transporter = MiTransporter::findOrFail($id);

        $transporter->update($request->only([
            'name',
            'transporter_gstn',
            'is_active'
        ]));

        return redirect()
            ->route('transporters.index')
            ->with('success', 'Transporter updated successfully');
    }
}
