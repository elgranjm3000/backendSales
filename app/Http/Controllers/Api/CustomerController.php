<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
            ->when($request->name, function($q) use ($request) {
                        $q->whereRaw('LOWER(name) LIKE ?', ['%' .Str::lower($request->name) . '%']);
              })
             ->when($request->name, fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%' .Str::lower($request->name) . '%']))
            ->when($request->document_number, fn($q) => $q->where('document_number', 'like', $request->document_number  . '%'))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'LIKE', "%{$request->search}%")
                          ->orWhere('email', 'LIKE', "%{$request->search}%")
                          ->orWhere('document_number', 'LIKE', "%{$request->search}%");
                });
            });

        $customers = $query->orderBy('name')->paginate($request->per_page ?? 100);

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
            'email' => 'required|email',
            'phone' => 'nullable|string|max:255',
            'document_type' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:255',
            'codigo' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('customers')->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                }),
            ],
            'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'sometimes|string|max:255',
            'additional_info' => 'nullable|array'
        ],[
            'codigo.unique' => 'El código proporcionado ya está registrado para otro cliente.',
            'email.required' => 'El email es requerido.',
            'name.required'=>'Su nombre es requerido'
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
    public function show(Customer $customer,$id): JsonResponse
    {
        $customer = Customer::with([
            'company',
            'quotes' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ])->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Cliente obtenido exitosamente'
        ]);
    }

    /**
     * Actualizar cliente
     */
    public function update(Request $request,$id): JsonResponse
    {
  
        $customer = Customer::find($id);

        $validated = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('customers')->ignore($id)],
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
    public function destroy(Customer $customer,$id): JsonResponse
    {
        $customer = Customer::find($id);

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
