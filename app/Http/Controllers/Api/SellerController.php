<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SellerController extends Controller
{
    /**
     * Lista de vendedores según el rol del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Seller::with(['user:id,name,email', 'company:id,name']);


        // Filtrar según el rol del usuario autenticado
        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                // Admin y Manager pueden ver todos los vendedores
                break;
            case User::ROLE_COMPANY:
                // Company solo puede ver vendedores de sus compañías
                $companyIds = $user->companies->pluck('id');    
                $query->whereIn('company_id', $request->company_id ? [$request->company_id] : $companyIds);
                break;
            case User::ROLE_SELLER:
                // Seller solo puede ver sus propios registros
                $query->where('user_id', $user->id);
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para listar vendedores'
                ], 403);
        }

        $sellers = $query->orderBy('created_at', 'desc')->get();
                        


        return response()->json([
            'success' => true,
            'data' => $sellers
        ]);
    }

    /**
     * Crear nuevo vendedor (crea nuevo usuario seller)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Verificar permisos
        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_COMPANY])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para crear vendedores'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            // Datos del usuario seller que se va a crear
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            
            // Datos del seller
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:50|unique:sellers,code',
            'description' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            'percent_sales' => 'nullable|numeric|min:0|max:100',
            'percent_receivable' => 'nullable|numeric|min:0|max:100',
            'inkeeper' => 'sometimes|boolean',
            'user_code' => 'nullable|string|max:50',
            'percent_gerencial_debit_note' => 'nullable|numeric|min:0|max:100',
            'percent_gerencial_credit_note' => 'nullable|numeric|min:0|max:100',
            'percent_returned_check' => 'nullable|numeric|min:0|max:100',
            'seller_status' => 'sometimes|in:active,inactive',
        ]);
        $message = implode(' | ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' =>  $message,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar permisos sobre la compañía
        $company = Company::find($request->company_id);
        $canCreateInCompany = false;

        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canCreateInCompany = true;
                break;
            case User::ROLE_COMPANY:
                $canCreateInCompany = $company->user_id === $user->id;
                break;
        }

        if (!$canCreateInCompany) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para crear vendedores en esta compañía'
            ], 403);
        }

        try {
            DB::beginTransaction();

            // 1. Crear el usuario con rol seller
            $sellerUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_SELLER,
                'status' => User::STATUS_ACTIVE,
            ]);

            // 2. Crear el registro de vendedor asociado al usuario
            $seller = Seller::create([
                'user_id' => $sellerUser->id,
                'company_id' => $request->company_id,
                'code' => $request->code,
                'description' => $request->description,
                'status' => $request->status,
                'percent_sales' => $request->percent_sales ?? 0,
                'percent_receivable' => $request->percent_receivable ?? 0,
                'inkeeper' => $request->inkeeper ?? false,
                'user_code' => $request->user_code,
                'percent_gerencial_debit_note' => $request->percent_gerencial_debit_note ?? 0,
                'percent_gerencial_credit_note' => $request->percent_gerencial_credit_note ?? 0,
                'percent_returned_check' => $request->percent_returned_check ?? 0,
                'seller_status' => $request->seller_status ?? Seller::STATUS_ACTIVE,
            ]);

            DB::commit();

            $seller->load(['user:id,name,email,role', 'company:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Vendedor creado exitosamente',
                'data' => $seller
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el vendedor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar vendedor específico
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $seller = Seller::with(['user:id,name,email,role', 'company:id,user_id,name'])->find($id);   

        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Vendedor no encontrado'
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
                $canView = $seller->company->user_id === $user->id;
                break;
            case User::ROLE_SELLER:
                $canView = $seller->company->user_id === $user->id;
                break;
        }

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver este vendedor'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $seller
        ]);
    }

    /**
     * Actualizar vendedor
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $seller = Seller::with(['company', 'user'])->find($id);

        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Vendedor no encontrado'
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
                $canUpdate = $seller->company->user_id === $user->id;
                break;
        }

        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para actualizar este vendedor'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            // Datos del usuario
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $seller->user->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            
            // Datos del seller
            'code' => 'sometimes|string|max:50|unique:sellers,code,' . $seller->id,
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|nullable|string|max:50',
            'percent_sales' => 'sometimes|nullable|numeric|min:0|max:100',
            'percent_receivable' => 'sometimes|nullable|numeric|min:0|max:100',
            'inkeeper' => 'sometimes|boolean',
            'user_code' => 'sometimes|nullable|string|max:50',
            'percent_gerencial_debit_note' => 'sometimes|nullable|numeric|min:0|max:100',
            'percent_gerencial_credit_note' => 'sometimes|nullable|numeric|min:0|max:100',
            'percent_returned_check' => 'sometimes|nullable|numeric|min:0|max:100',
            'seller_status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Actualizar datos del usuario si se proporcionan
            $userUpdateData = [];
            if ($request->has('name')) $userUpdateData['name'] = $request->name;
            if ($request->has('seller_status')) $userUpdateData['status'] = $request->seller_status;
            if ($request->has('email')) $userUpdateData['email'] = $request->email;
            if ($request->has('phone')) $userUpdateData['phone'] = $request->phone;
            if ($request->filled('password')) $userUpdateData['password'] = Hash::make($request->password);

            if (!empty($userUpdateData)) {
                $seller->user->update($userUpdateData);
            }

            // Actualizar datos del seller
            $sellerUpdateData = $request->only([
                'code', 'description', 'status', 'percent_sales', 'percent_receivable',
                'inkeeper', 'user_code', 'percent_gerencial_debit_note',
                'percent_gerencial_credit_note', 'percent_returned_check', 'seller_status'
            ]);

            if (!empty($sellerUpdateData)) {
                $seller->update($sellerUpdateData);
            }

            DB::commit();

            $seller->load(['user:id,name,email,role', 'company:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Vendedor actualizado exitosamente',
                'data' => $seller
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el vendedor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar vendedor (elimina usuario y seller)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $seller = Seller::with(['company', 'user'])->find($id);

        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Vendedor no encontrado'
            ], 404);
        }

        // Verificar permisos
        $canDelete = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
            case User::ROLE_MANAGER:
                $canDelete = true;
                break;
            case User::ROLE_COMPANY:
                $canDelete = $seller->company->user_id === $user->id;
                break;
        }

        if (!$canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar este vendedor'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $sellerUser = $seller->user;
            
            // Eliminar el registro de vendedor (esto eliminará automáticamente el usuario por la foreign key cascade)
            $seller->delete();
            
            // Si quieres eliminar también el usuario (opcional, según tu lógica de negocio)
            // $sellerUser->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vendedor eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el vendedor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener vendedores por compañía
     */
    public function getByCompany(Request $request, $companyId)
    {
        $user = $request->user();
        $company = Company::find($companyId);

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
                'message' => 'No tienes permisos para ver vendedores de esta compañía'
            ], 403);
        }

        $sellers = Seller::where('company_id', $companyId)
                         ->with('user:id,name,email,role')
                         ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $sellers
        ]);
    }
}