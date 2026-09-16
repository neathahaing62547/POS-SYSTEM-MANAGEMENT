<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query();
        $totalbrand = Brand::count();
        $activebrand = Brand::where('status', 'active')->count();
        $inactivebrand = Brand::where('status', 'inactive')->count();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $brands = $query->latest()->paginate(10);

        return response()->json([
            'message' => 'Brands retrieved successfully',
            'summary' => [
                'total_brands' => $totalbrand,
                'active_brands' => $activebrand,
                'inactive_brands' => $inactivebrand,
            ],
            'data' => $brands
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand = Brand::create($validated);
        return response()->json([
            'message' => 'Brand created successfully',
            'data' => $brand
        ], 201);
    }
    public function show(Brand $brand)
    {
        return response()->json([
            'message' => 'Brand retrieved successfully',
            'data' => $brand
        ]);
    }
    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'logo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'sometimes|nullable|in:active,inactive',
        ]);

        if ($request->hasFile('logo')) {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        } else {
            unset($validated['logo']);
        }

        $brand->update($validated);
        return response()->json([
            'message' => 'Brand updated successfully',
            'data' => $brand
        ]);
    }
    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this brand because it has products.'
            ], 422);
        }

        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();
        return response()->json([
            'message' => 'Brand deleted successfully',
            'data' => $brand
        ]);
    }
}