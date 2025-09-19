<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Mostrar lista de categorías
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::with('company')
            ->when($request->company_id, fn($q) => $q->byCompany($request->company_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('name', 'LIKE', "%{$request->search}%"));

        $categories = $query->orderBy('name')->get(); //paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Categorías obtenidas exitosamente'
        ]);
    }

    /**
     * Crear nueva categoría
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['active', 'inactive'])]
        ]);

        $category = Category::create($validated);
        $category->load('company');

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Categoría creada exitosamente'
        ], 201);
    }

    /**
     * Mostrar categoría específica
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['company', 'products' => function ($query) {
            $query->active()->orderBy('name');
        }]);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Categoría obtenida exitosamente'
        ]);
    }

    /**
     * Actualizar categoría
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])]
        ]);

        $category->update($validated);
        $category->load('company');

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Categoría actualizada exitosamente'
        ]);
    }

    /**
     * Eliminar categoría
     */
    public function destroy(Category $category): JsonResponse
    {
        // Verificar si tiene productos asociados
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene productos asociados'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }

    /**
     * Obtener categorías activas para select
     */
    public function active(Request $request): JsonResponse
    {
        $categories = Category::active()
            ->when($request->company_id, fn($q) => $q->byCompany($request->company_id))
            ->select('id', 'name', 'company_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}