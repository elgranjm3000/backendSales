<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;


class ProductController extends Controller
{
    /**
     * Mostrar lista de productos
     */
    public function index(Request $request): JsonResponse
    {
         try {
          $perPage = min(50, (int)($request->per_page ?? 50));

          $query = Product::query()
              ->with(['company:id,name', 'category:id,description'])
              ->where('status', 'active')
              ->when($request->company_id, fn($q) => $q->byCompany($request->company_id))
              ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
              ->when($request->status, fn($q) => $q->where('status', $request->status))
              ->when($request->low_stock, fn($q) => $q->lowStock())
              ->when($request->search, fn($q) => $q->search($request->search))
              ->when($request->description, function($q) use ($request) {
                        $q->whereRaw('LOWER(description) LIKE ?', [Str::lower($request->description) . '%']);
              })
              ->when($request->code, function($q) use ($request) {
                        $q->whereRaw('LOWER(code) LIKE ?', [Str::lower($request->code) . '%']);
              });

          //$paginated = $query->orderBy('name')->paginate($perPage);
          $paginated = $query->reorder()
                   ->orderByRaw('LOWER(name) ASC')
                   ->paginate($perPage);
        
            // Convertir a colección y agregar image_url
         $products = collect($paginated->items())->map(function ($product) {
                    // 1. Extraemos los bytes del recurso de PostgreSQL si es que viene como un resource de PHP
                    $binaryData = $product->product_image;
                    if (is_resource($binaryData)) {
                        $binaryData = stream_get_contents($binaryData);
                    }
                
                    // 2. Ocultamos temporalmente el campo binario original para que $product->toArray() no se rompa
                    $product->makeHidden(['product_image']);
                    $productArray = $product->toArray();
                
                    // 3. Agregar tu URL en Base64 utilizando los bytes ya limpios
                     $mimeType = $product->image_type;
                      if (!str_contains($mimeType, '/')) {
                          // Es solo el tipo (jpg, png), agregar prefix
                          $mimeType = 'image/' . $mimeType;
                      }
                    
                      if ($mimeType && $binaryData) {
                          $productArray['image_url'] = "data:{$mimeType};base64," . base64_encode($binaryData);
                      } else {
                          $productArray['image_url'] = null;
                      }

                
                    return $productArray;
        });


          // ✅ Devolver en formato compatible con el frontend existente
          return response()->json([
              'success' => true,
              'data' => $products, // Solo el array de productos
              'pagination' => [
                  'current_page' => $paginated->currentPage(),
                  'per_page' => $paginated->perPage(),
                  'total' => $paginated->total(),
              ],
              'message' => 'Productos obtenidos exitosamente'
          ]);

      } catch (\Exception $e) {
          return response()->json([
              'success' => false,
              'message' => 'Error: ' . $e->getMessage()
          ], 500);
      }

}

    /**
     * Crear nuevo producto
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:products,code|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'image' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'category_id' => 'required|exists:categories,id',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'barcode' => 'nullable|string|max:255',
            'weight' => 'required|numeric|min:0',
            'attributes' => 'nullable|array'
        ]);

        $product = Product::create($validated);
        $product->load(['company', 'category']);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Producto creado exitosamente'
        ], 201);
    }

    /**
     * Mostrar producto específico
     */
    public function show(Product $product,$id): JsonResponse
    {
        
        $product = Product::with('company','category')->find($id);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Producto obtenido exitosamente'
        ]);
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products')->ignore($product->id)],
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'cost' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'min_stock' => 'sometimes|required|integer|min:0',
            'image' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'category_id' => 'sometimes|required|exists:categories,id',
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'barcode' => 'nullable|string|max:255',
            'weight' => 'sometimes|required|numeric|min:0',
            'attributes' => 'nullable|array'
        ]);

        $product->update($validated);
        $product->load(['company', 'category']);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Producto actualizado exitosamente'
        ]);
    }

    /**
     * Eliminar producto
     */
    public function destroy(Product $product): JsonResponse
    {
        // Verificar si tiene items de cotización asociados
        if ($product->quoteItems()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el producto porque está en cotizaciones'
            ], 400);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado exitosamente'
        ]);
    }

    /**
     * Obtener productos activos para select
     */
    public function active(Request $request): JsonResponse
    {
        $products = Product::active()
            ->when($request->company_id, fn($q) => $q->byCompany($request->company_id))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->select('id', 'name', 'code', 'price', 'stock', 'company_id', 'category_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Obtener productos con stock bajo
     */
    public function lowStock(Request $request): JsonResponse
    {
        $products = Product::lowStock()
            ->with(['company', 'category'])
            ->when($request->company_id, fn($q) => $q->byCompany($request->company_id))
            ->orderBy('stock', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Productos con stock bajo obtenidos exitosamente'
        ]);
    }

    /**
     * Actualizar stock del producto
     */
    public function updateStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
            'operation' => ['required', Rule::in(['set', 'add', 'subtract'])]
        ]);

        switch ($validated['operation']) {
            case 'set':
                $product->stock = $validated['stock'];
                break;
            case 'add':
                $product->stock += $validated['stock'];
                break;
            case 'subtract':
                $product->stock = max(0, $product->stock - $validated['stock']);
                break;
        }

        $product->save();

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Stock actualizado exitosamente'
        ]);
    }
}