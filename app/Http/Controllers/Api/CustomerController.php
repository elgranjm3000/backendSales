<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Mostrar lista de clientes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with('company')
            ->when($request->company_id, fn($q) => $q->byCompany($request->company_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'LIKE', "%{$request->search}%")
                          ->orWhere('email', 'LIKE', "%{$request->search}%")
                          ->orWhere('document_number', 'LIKE', "%{$request->search}%");
                });
            });

        $customers = $query->orderBy('name')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $customers,
            'message' => 'Clientes obtenidos exitosamente'
        ]);
    }

    /**
     * Crear nuevo cliente
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'sometimes|string|max:255',
            'additional_info' => 'nullable|array'
        ]);

        $customer = Customer::create($validated);
        $customer->load('company');

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Cliente creado exitosamente'
        ], 201);
    }

    /**
     * Mostrar cliente específico
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['company', 'quotes' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Cliente obtenido exitosamente'
        ]);
    }

    /**
     * Actualizar cliente
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('customers')->ignore($customer->id)],
            'phone' => 'nullable|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'sometimes|string|max:255',
            'additional_info' => 'nullable|array'
        ]);

        $customer->update($validated);
        $customer->load('company');

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Cliente actualizado exitosamente'
        ]);
    }

    /**
     * Eliminar cliente
     */
    public function destroy(Customer $customer): JsonResponse
    {
        // Verificar si tiene cotizaciones asociadas
        if ($customer->quotes()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el cliente porque tiene cotizaciones asociadas'
            ], 400);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }

    /**
     * Obtener clientes activos para select
     */
    public function active(Request $request): JsonResponse
    {
        $customers = Customer::active()
            ->when($request->company_id, fn($q) => $q->byCompany($request->company_id))
            ->select('id', 'name', 'email', 'company_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }
}
