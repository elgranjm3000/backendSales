<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\KeySystemItem;

class CompanyController extends Controller
{
    /**
     * Lista de compañías según el rol del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Company::with('user:id,name,email');

        // Filtrar según el rol del usuario autenticado
        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                // Admin y Manager pueden ver todas las compañías
                break;
            case User::ROLE_COMPANY:
                // Company solo puede ver sus propias compañías
                $query->where('user_id', $user->id);
                break;
            case User::ROLE_SELLER:
              //  $query = Seller::with('user:id,name,email');
                  $seller = Seller::where('user_id', $user->id)->first();
                if ($seller) {
                    $query->where('user_id', $seller->company_id);
                } else {
                    // Si el vendedor no está asociado a ninguna compañía, devolver vacío
                    $query->whereRaw('1 = 0');
                }

                // Company solo puede ver sus propias compañías
            //    $query->where('user_id', $user->id);
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para listar compañías'
                ], 403);
        }

        $companies = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }

    /**
     * Crear nueva compañía
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Verificar permisos
        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_COMPANY])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para crear compañías'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'sometimes|string|max:255',
            'contact' => 'nullable|string|max:255',
            'serial_no' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive',
            'rif' => 'required|string|max:20|unique:companies,rif',
            'key_system_items_id' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $keyactivate = KeySystemItem::where('key_activation', $request->key_system_items_id)->first();

        if (!$keyactivate) {
            return response()->json([
                'success' => false,
                'message' => 'Clave de activación inválida'
            ], 422);
        }

        // Determinar el user_id
        $userId = $request->user_id;
        if ($user->role === User::ROLE_COMPANY) {
            // Si es company, solo puede crear para sí mismo
            $userId = $user->id;
        } elseif (!$userId) {
            // Si admin o manager no especifican user_id, error
            return response()->json([
                'success' => false,
                'message' => 'Debe especificar el user_id para la compañía'
            ], 422);
        }

        // Verificar que el usuario existe y tiene el rol company
        $companyUser = User::find($userId);
        if (!$companyUser || $companyUser->role !== User::ROLE_COMPANY) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario debe tener rol de company'
            ], 422);
        }

        $company = Company::create([
            'user_id' => $userId,
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email ?? '00',
            'contact' => $request->contact,
            'serial_no' => $request->serial_no,
            'status' => $request->status ?? Company::STATUS_ACTIVE,
            'rif' => $request->rif,
            'key_system_items_id'=> $keyactivate->id,
        ]);

        $company->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Compañía creada exitosamente',
            'data' => $company
        ], 201);
    }

    /**
     * Mostrar compañía específica
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $company = Company::with(['user:id,name,email', 'sellers.user:id,name,email'])->find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Compañía no encontrada'
            ], 404);
        }

        // Verificar permisos
        $canView = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canView = true;
                break;
            case User::ROLE_COMPANY:
                $canView = $company->user_id === $user->id;
                break;
        }

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver esta compañía'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }

    /**
     * Actualizar compañía
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Compañía no encontrada'
            ], 404);
        }

        // Verificar permisos
        $canUpdate = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canUpdate = true;
                break;
            case User::ROLE_COMPANY:
                $canUpdate = $company->user_id === $user->id;
                break;
        }

        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para actualizar esta compañía'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string|max:500',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|string|max:255',
            'contact' => 'sometimes|nullable|string|max:255',
            'serial_no' => 'sometimes|nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only([
            'name', 'description', 'address', 'phone', 
            'email', 'contact', 'serial_no', 'status'
        ]);

        $company->update($updateData);
        $company->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Compañía actualizada exitosamente',
            'data' => $company
        ]);
    }

    /**
     * Eliminar compañía
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Compañía no encontrada'
            ], 404);
        }

        // Solo admin puede eliminar compañías
        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar compañías'
            ], 403);
        }

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compañía eliminada exitosamente'
        ]);
    }

    /**
     * Obtener vendedores de una compañía
     */
    public function sellers(Request $request, $id)
    {
       
        $user = $request->user();
        $company = Company::find($id);
     //   $sellers = Seller::where('company_id', $id)->get();
     
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Compañía no encontrada'
            ], 404);
        }

        // Verificar permisos
        $canView = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canView = true;
                break;
            case User::ROLE_COMPANY:
                $canView = $company->user_id === $user->id;
                break;
        }

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver los vendedores de esta compañía'
            ], 403);
        }

        $sellers = $company->sellers()->with('user:id,name,email')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $sellers
        ]);
    }
}