<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Acceso;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                // Admin y Manager pueden ver todas las compañías
                break;
            case \App\Enums\UserRole::COMPANY:
                // Company solo puede ver sus propias compañías
                $query->where('user_id', $user->id);
                break;
            case \App\Enums\UserRole::SELLER:
              //  $query = Seller::with('user:id,name,email');
                $seller = Seller::where('user_id', $user->id)->get();
                if ($seller) {
                    //$query->where('id', $seller->company_id);
                    $companyIds = $seller->pluck('company_id')->unique();
                    $query->whereIn('id', $companyIds);
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
        if (!in_array($user->role, [\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::MANAGER, \App\Enums\UserRole::COMPANY])) {
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
        if ($user->role === \App\Enums\UserRole::COMPANY) {
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
        if (!$companyUser || $companyUser->role !== \App\Enums\UserRole::COMPANY) {
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
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                $canView = true;
                break;
            case \App\Enums\UserRole::COMPANY:
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
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                $canUpdate = true;
                break;
            case \App\Enums\UserRole::COMPANY:
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
        if ($user->role !== \App\Enums\UserRole::ADMIN) {
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
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                $canView = true;
                break;
            case \App\Enums\UserRole::COMPANY:
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

    /**
     * Buscar en tabla acceso por id_fiscal y correo_electronico
     * Query específica para búsqueda en tabla acceso
     */
    public function findInAcceso(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_fiscal' => 'required|string|max:50',
            'correo_electronico' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Usar la query SQL específica solicitada para tabla acceso
        $query = "
            SELECT id_fiscal
            FROM acceso
            WHERE id_fiscal = ? AND correo_electronico = ?
            LIMIT 1
        ";

        $result = DB::select($query, [$request->id_fiscal, $request->correo_electronico]);

        if (empty($result)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ningún registro en acceso con esos datos',
                'data' => [
                    'id_fiscal' => $request->id_fiscal,
                    'correo_electronico' => $request->correo_electronico
                ]
            ], 404);
        }

        // Buscar el registro completo en acceso
        $acceso = Acceso::where('id_fiscal', $request->id_fiscal)
            ->where('correo_electronico', $request->correo_electronico)
            ->first();

        if (!$acceso) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registro encontrado en tabla acceso',
            'data' => [
                'id_fiscal' => $acceso->id_fiscal,
                'nombre' => $acceso->name ?? null,
                'correo_electronico' => $acceso->correo_electronico,
                'codigo' => $acceso->codigo ?? null,
                'description' => $acceso->description ?? null,
                'address' => $acceso->address ?? null,
                'country' => $acceso->country ?? null,
                'province' => $acceso->province ?? null,
                'city' => $acceso->city ?? null,
                'phone' => $acceso->phone ?? null,
                'contact' => $acceso->contact ?? null,
            ]
        ]);
    }

    /**
     * Buscar compañía por RIF y email
     * Query específica para búsqueda exacta
     */
    public function findByRifAndEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rif' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Usar la query SQL específica solicitada
        $query = "
            SELECT id
            FROM companies
            WHERE rif = ? AND email = ?
            LIMIT 1
        ";

        $company = DB::select($query, [$request->rif, $request->email]);

        if (empty($company)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna compañía con ese RIF y email',
                'data' => [
                    'rif' => $request->rif,
                    'email' => $request->email
                ]
            ], 404);
        }

        // Obtener el ID de la compañía encontrada
        $companyId = $company[0]->id;

        // Cargar la compañía completa con sus relaciones
        $companyData = Company::with('user:id,name,email')
            ->find($companyId);

        if (!$companyData) {
            return response()->json([
                'success' => false,
                'message' => 'Compañía no encontrada'
            ], 404);
        }

        // Verificar permisos según el rol del usuario autenticado
        $user = $request->user();
        $canView = false;

        switch ($user->role) {
            case \App\Enums\UserRole::ADMIN:
            case \App\Enums\UserRole::MANAGER:
                $canView = true;
                break;
            case \App\Enums\UserRole::COMPANY:
                $canView = $companyData->user_id === $user->id;
                break;
            case \App\Enums\UserRole::SELLER:
                $seller = Seller::where('user_id', $user->id)
                    ->where('company_id', $companyId)
                    ->first();
                $canView = $seller !== null;
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
            'message' => 'Compañía encontrada',
            'data' => $companyData
        ]);
    }

    /**
     * Validar si una compañía existe por su código/RIF
     * Flujo completo:
     * 1. Buscar en tabla 'acceso' por codigo (RIF)
     * 2. Si existe en acceso, buscar en tabla 'companies' por email y rif
     * 3. Si NO existe en companies → crearla
     * 4. Si SÍ existe en companies → retornar ID
     */
    public function validateCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'El código es requerido',
                'errors' => $validator->errors()
            ], 422);
        }

        // PASO 1: Buscar en tabla 'acceso' por codigo (RIF)
        $acceso = Acceso::where('codigo', $request->code)->first();

        if (!$acceso) {
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'La compañía no existe en la tabla acceso'
            ], 404);
        }

        // PASO 2: Buscar en tabla 'companies' usando email y rif
        $company = null;
        $email = $request->input('email') ?: ($acceso->email ?: 'contacto@' . strtolower(str_replace(['-', ' '], '', $request->code)) . '.com');
        $rif = $request->code; // El code es el RIF

        // Primero intentar buscar por email si está disponible
        if ($email) {
            $company = \App\Models\Company::where('email', $email)
                ->where('rif', $rif)
                ->first();
        }

        // Si no encuentra por email, buscar solo por rif
        if (!$company) {
            $company = \App\Models\Company::where('rif', $rif)->first();
        }

        // PASO 3: Si NO existe en companies, crearla
        if (!$company) {
            try {
                DB::beginTransaction();

                $company = \App\Models\Company::create([
                    'name' => $acceso->name ?? 'Empresa ' . $rif,
                    'rif' => $rif,
                    'email' => $email,  // Usa email con fallback (request, acceso, o generado)
                    'description' => $acceso->description ?? null,
                    'address' => $acceso->address ?? null,
                    'country' => $acceso->country ?? 'Venezuela',
                    'province' => $acceso->province ?? null,
                    'city' => $acceso->city ?? null,
                    'phone' => $acceso->phone ?? null,
                    'contact' => $acceso->contact ?? null,
                    'status' => 'active',
                    'key_system_items_id' => 1,
                    'serial_no' => $acceso->codigo ?? null,
                ]);

                DB::commit();

                Log::info('Company created from acceso table', [
                    'company_id' => $company->id,
                    'rif' => $rif,
                    'email' => $email
                ]);

                return response()->json([
                    'success' => true,
                    'exists' => true,
                    'created' => true,
                    'message' => 'Compañía creada exitosamente',
                    'data' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'rif' => $company->rif,
                        'email' => $company->email,
                        'status' => $company->status,
                        'created_at' => $company->created_at
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error creating company from acceso', [
                    'rif' => $rif,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la compañía: ' . $e->getMessage()
                ], 500);
            }
        }

        // PASO 4: Si SÍ existe en companies, retornar ID
        return response()->json([
            'success' => true,
            'exists' => true,
            'created' => false,
            'message' => 'La compañía existe',
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'rif' => $company->rif,
                'email' => $company->email,
                'status' => $company->status,
                'created_at' => $company->created_at,
                'updated_at' => $company->updated_at
            ]
        ]);
    }
}