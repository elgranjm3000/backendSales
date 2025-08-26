<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\KeySystemItem;

class AuthController extends Controller
{
    /**
     * Login de usuario
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::with('companies')->where('email', $request->email)->first();


        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo'
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'companies' => $user->companies->map(function ($company) {
                        return [
                            'id' => $company->id,
                            'name' => $company->name,                           
                        ];
                    }),
                ],
                'token' => $token
            ]
        ]);
    }

    /**
     * Logout de usuario
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    /**
     * Crear nuevo usuario
     */
    public function register(Request $request)
    {     


        $validator = Validator::make($request->all(), [            
            'email' => 'required|string|email|max:255|unique:users',   
            'name' => 'required|string|max:255',       
            'role' => 'required|in:company',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'sometimes|in:active,inactive',
            'key_activation' => 'required|string', 
            'phone' => 'sometimes|nullable|string|max:20',
            'rif' => 'required|string|max:20|unique:companies,rif',
            'companyName' => 'required|string|max:255',
            'contactPerson' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'country' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',            
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar si la key_activation existe en la tabla companies
        $keyactivate = KeySystemItem::where('key_activation', $request->key_activation)->first();

        if (!$keyactivate) {
            return response()->json([
                'success' => false,
                'message' => 'Clave de activación inválida'
            ], 422);
        }

        // Crear el nuevo usuario
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status ?? User::STATUS_ACTIVE,
            'password' => Hash::make($request->password),
        ]);
        $this->createCompany($newUser, $request,$keyactivate);

        // Si el rol es 'seller', crear automáticamente el registro en la tabla sellers
     

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
     * Crear registro de vendedor automáticamente
     */
    private function createSellerRecord(User $user, Company $company)
    {
        // Generar código único para el vendedor
        $code = 'SELL-' . $company->id . '-' . $user->id . '-' . time();
        
        $seller = new \App\Models\Seller();
        $seller->user_id = $user->id;
        $seller->company_id = $company->id;
        $seller->code = $code;
        $seller->description = 'Vendedor registrado automáticamente';
        $seller->status = 'active';
        $seller->percent_sales = 0;
        $seller->percent_receivable = 0;
        $seller->inkeeper = 0;
        $seller->percent_gerencial_debit_note = 0;
        $seller->percent_gerencial_credit_note = 0;
        $seller->percent_returned_check = 0;
        $seller->seller_status = 'active';
        $seller->save();

        return $seller;
    }

    private function createCompany(User $user, $request,$keyactivate){
        $company = new Company();
        $company->user_id = $user->id;
        $company->name = $request->companyName;
        $company->rif = $request->rif;
        $company->contact = $request->contactPerson;
        $company->address = $request->address;
        $company->country = $request->country;
        $company->province = $request->province;
        $company->city = $request->city;
        $company->phone = $request->phone ?? null;
        $company->email = $request->email; // Valor por defecto
        $company->key_system_items_id = $keyactivate->id; // Asociar la clave de activación
        $company->serial_no = null; // Puedes ajustar esto según tus necesidades
        $company->status = 'active';
        $company->save();

        return $company;
    }
}