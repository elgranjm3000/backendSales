<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Lista de usuarios según el rol del usuario autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = User::query();

        // Filtrar según el rol del usuario autenticado
        switch ($user->role) {
            case User::ROLE_ADMIN:
                // Admin puede ver todos los usuarios
                break;
            case User::ROLE_MANAGER:
                // Manager puede ver companies y sellers
                $query->whereIn('role', [User::ROLE_COMPANY, User::ROLE_SELLER]);
                break;
            case User::ROLE_COMPANY:
                // Company solo puede ver sellers
                $query->where('role', User::ROLE_SELLER);
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para listar usuarios'
                ], 403);
        }

        $users = $query->select(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at'])
                       ->orderBy('created_at', 'desc')
                       ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Crear nuevo usuario
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,manager,company,seller',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar permisos
        if (!$user->canCreateUser($request->role)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para crear este tipo de usuario'
            ], 403);
        }

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status ?? User::STATUS_ACTIVE,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => [
                'id' => $newUser->id,
                'name' => $newUser->name,
                'email' => $newUser->email,
                'phone' => $newUser->phone,
                'role' => $newUser->role,
                'status' => $newUser->status,
                'created_at' => $newUser->created_at,
            ]
        ], 201);
    }

    /**
     * Mostrar usuario específico
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Verificar permisos para ver el usuario
        $canView = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
                $canView = true;
                break;
            case User::ROLE_MANAGER:
                $canView = in_array($targetUser->role, [User::ROLE_COMPANY, User::ROLE_SELLER]);
                break;
            case User::ROLE_COMPANY:
                $canView = $targetUser->role === User::ROLE_SELLER;
                break;
        }

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver este usuario'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'phone' => $targetUser->phone,
                'role' => $targetUser->role,
                'status' => $targetUser->status,
                'avatar' => $targetUser->avatar,
                'created_at' => $targetUser->created_at,
            ]
        ]);
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $targetUser->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar permisos para actualizar
        $canUpdate = false;
        switch ($user->role) {
            case User::ROLE_ADMIN:
                $canUpdate = true;
                break;
            case User::ROLE_MANAGER:
                $canUpdate = in_array($targetUser->role, [User::ROLE_COMPANY, User::ROLE_SELLER]);
                break;
            case User::ROLE_COMPANY:
                $canUpdate = $targetUser->role === User::ROLE_SELLER;
                break;
        }

        if (!$canUpdate) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para actualizar este usuario'
            ], 403);
        }

        $updateData = $request->only(['name', 'email', 'phone', 'status']);
        
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $targetUser->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'phone' => $targetUser->phone,
                'role' => $targetUser->role,
                'status' => $targetUser->status,
                'updated_at' => $targetUser->updated_at,
            ]
        ]);
    }

    /**
     * Eliminar usuario
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Solo admin puede eliminar usuarios
        if ($user->role !== User::ROLE_ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar usuarios'
            ], 403);
        }

        $targetUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }
}