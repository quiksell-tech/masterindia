<div class="form-group">
    <label>Item Name</label>
    <input type="text" name="item_name" class="form-control"
           value="{{ old('item_name', $item->item_name ?? '') }}" required>
</div>

<div class="form-group">
    <label>Item Code</label>
    <input type="text" name="item_code" class="form-control"
           value="{{ old('item_code', $item->item_code ?? '') }}" required>
</div>

<div class="form-group">
    <label>HSN Code</label>
    <input type="text" name="hsn_code" class="form-control"
           value="{{ old('hsn_code', $item->hsn_code ?? '') }}">
</div>

<div class="form-group">
    <label>Tax Percentage</label>
    <input type="number" step="0.01" name="tax_percentage" class="form-control"
           value="{{ old('tax_percentage', $item->tax_percentage ?? '') }}" required>
</div>

<div class="form-group">
    <label>Description</label>
    <textarea name="item_description" class="form-control">{{ old('item_description', $item->item_description ?? '') }}</textarea>
</div>

@if(isset($item))
    <div class="form-group">
        <label>Status</label>
        <select name="is_active" class="form-control">
            <option value="Y" {{ $item->is_active == 'Y' ? 'selected' : '' }}>Active</option>
            <option value="N" {{ $item->is_active == 'N' ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>
@endif
