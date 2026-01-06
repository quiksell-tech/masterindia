<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\MiItem;

class ItemController extends Controller
{
    public function index()
    {
        $items = MiItem::orderByDesc('item_id')->paginate(25);
        return view('items.index', compact('items'));
    }

    public function create()
    {
        return view('items.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_name'      => 'required',
            'item_code'      => 'required|unique:mi_items,item_code',
            'tax_percentage' => 'required|numeric',
        ]);

        MiItem::create([
            'item_name'        => $request->item_name,
            'item_description' => $request->item_description,
            'item_code'        => $request->item_code,
            'hsn_code'         => $request->hsn_code,
            'tax_percentage'   => $request->tax_percentage,
            'is_active'        => 'Y',
        ]);

        return redirect()->route('items.index')
            ->with('success', 'Item created successfully');
    }

    public function edit($id)
    {
        $item = MiItem::findOrFail($id);
        return view('items.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = MiItem::findOrFail($id);

        $request->validate([
            'item_name'      => 'required',
            'item_code'      => 'required|unique:mi_items,item_code,' . $item->item_id . ',item_id',
            'tax_percentage' => 'required|numeric',
        ]);

        $item->update([
            'item_name'        => $request->item_name,
            'item_description' => $request->item_description,
            'item_code'        => $request->item_code,
            'hsn_code'         => $request->hsn_code,
            'tax_percentage'   => $request->tax_percentage,
            'is_active'        => $request->is_active ?? 'Y',
        ]);

        return redirect()->route('items.index')
            ->with('success', 'Item updated successfully');
    }
}
