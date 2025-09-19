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
use App\Mail\CompanyValidationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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
        ], [
                // Mensajes personalizados por campo y regla
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Debes ingresar un correo válido.',
                'email.unique' => 'Este correo ya está registrado.',
                'email.max' => 'El correo no puede exceder los 255 caracteres.',

                'name.required' => 'El nombre es obligatorio.',
                'name.max' => 'El nombre no puede exceder los 255 caracteres.',

                'role.required' => 'El rol es obligatorio.',
                'role.in' => 'El rol debe ser "company".',

                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',

                'status.in' => 'El estado debe ser "active" o "inactive".',

                'key_activation.required' => 'La clave de activación es obligatoria.',

                'phone.max' => 'El número de teléfono no puede exceder los 20 caracteres.',

                'rif.required' => 'El RIF es obligatorio.',
                'rif.max' => 'El RIF no puede exceder los 20 caracteres.',
                'rif.unique' => 'Este RIF ya está registrado en otra empresa.',
        ]);
        $message = implode(' | ', $validator->errors()->all());
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $message,
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
        $company->contact = $request->name;
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


    public function checkCompanyInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'rif' => 'required|string|max:20',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo válido.',
            'rif.required' => 'El RIF es obligatorio.',
            'rif.max' => 'El RIF no puede exceder los 20 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        // Buscar en la tabla companies existente
        $company = Company::where('email', $request->email)
                         ->where('rif', $request->rif)
                         ->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Contacte con su administrador de Chrystal',
                'code' => 'COMPANY_NOT_FOUND'
            ], 404);
        }

        // Verificar si ya tiene un usuario registrado (ya está en uso)
        if ($company->user_id && $company->user) {
            return response()->json([
                'success' => false,
                'message' => 'Esta empresa ya tiene un usuario registrado en el sistema',
                'code' => 'COMPANY_ALREADY_HAS_USER'
            ], 409);
        }

        // Obtener la licencia desde KeySystemItem relacionado
        $licenseKey = $company->key_system_items_id;      
        //$companyLicense = Company::with('keySystemItem')->find($licenseKey);        
        $companyLicense = Company::with('keySystemItem')->active()->get()->first();


        $partialLicense = $this->maskLicense($companyLicense->keySystemItem->key_activation);

        return response()->json([
            'success' => true,
            'message' => 'Información empresarial encontrada',
            'data' => [
                'company_id' => $company->id,
                'name' => $company->name,
                'address' => $company->address,
                'phone' => $company->phone,
                'email' => $company->email,
                'contact' => $company->contact,
                'license' => $partialLicense, // Licencia parcialmente oculta
                'rif' => $company->rif,
            ],
            'question' => '¿Desea registrar esta compañía?'
        ]);
    }


     /**
     * NUEVA FUNCIONALIDAD: Confirmar registro de empresa
     * Paso 2: Cliente acepta registrar la empresa
     */
    public function confirmCompanyRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'confirm' => 'required|boolean'
        ], [
            'company_id.required' => 'ID de empresa es obligatorio.',
            'company_id.exists' => 'La empresa especificada no existe.',
            'confirm.required' => 'La confirmación es obligatoria.',
            'confirm.boolean' => 'La confirmación debe ser true o false.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$request->confirm) {
            return response()->json([
                'success' => true,
                'message' => 'Registro cancelado por el usuario'
            ]);
        }

        $company = Company::findOrFail($request->company_id);

        // Verificar nuevamente que no tenga usuario
        if ($company->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta empresa ya tiene un usuario registrado'
            ], 409);
        }

        // Generar código de validación
        $validationCode = $this->generateValidationCode();
        
        // Guardar código en Cache con clave única (15 minutos)
        $cacheKey = "company_validation_{$company->id}_{$company->email}";
        Cache::put($cacheKey, $validationCode, 15 * 60); // 15 minutos en segundos

        // Enviar email con código de validación
        try {
            Mail::to($company->email)->send(new CompanyValidationMail($validationCode, $company));
            
            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un código de validación a su correo electrónico',
                'data' => [
                    'email' => $this->maskEmail($company->email),
                    'expires_in_minutes' => 15
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el código de validación. Intente nuevamente.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * NUEVA FUNCIONALIDAD: Validar código y permitir creación de clave
     * Paso 3: Validar código enviado por email
     */
    public function validateCompanyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'email' => 'required|email',
            'validation_code' => 'required|string|size:6',
        ], [
            'company_id.required' => 'ID de empresa es obligatorio.',
            'company_id.exists' => 'La empresa especificada no existe.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo válido.',
            'validation_code.required' => 'El código de validación es obligatorio.',
            'validation_code.size' => 'El código debe tener exactamente 6 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $company = Company::findOrFail($request->company_id);

        // Verificar que el email coincida con el de la empresa
        if ($company->email !== $request->email) {
            return response()->json([
                'success' => false,
                'message' => 'El correo no coincide con el de la empresa',
                'code' => 'EMAIL_MISMATCH'
            ], 422);
        }

        // Verificar código en cache
        $cacheKey = "company_validation_{$company->id}_{$company->email}";
        $storedCode = Cache::get($cacheKey);

        if (!$storedCode || $storedCode !== $request->validation_code) {
            return response()->json([
                'success' => false,
                'message' => 'Código de validación inválido o expirado',
                'code' => 'INVALID_CODE'
            ], 422);
        }

        // Marcar código como usado (eliminar del cache y crear nuevo token)
        Cache::forget($cacheKey);
        
        // Crear token de validación válido por 1 hora
        $validationToken = $this->generateValidationToken($company->id, $company->email,$company->name);
        $tokenKey = "validation_token_{$company->id}";
        Cache::put($tokenKey, $validationToken, 60 * 60); // 1 hora

        return response()->json([
            'success' => true,
            'message' => 'Código validado correctamente. Ahora puede crear su clave de acceso.',
            'data' => [
                'validation_token' => $validationToken,
                'company_id' => $company->id,
                'expires_in_minutes' => 60
            ]
        ]);
    }

    /**
     * NUEVA FUNCIONALIDAD: Registro final con creación de clave
     * Paso 4: Crear usuario y asociarlo a la empresa existente
     */
    public function completeCompanyRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'validation_token' => 'required|string',
            'company_id' => 'required',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'validation_token.required' => 'Token de validación es obligatorio.',
            'company_id.required' => 'ID de empresa es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => implode(' | ', $validator->errors()->all()),
                'errors' => $validator->errors()
            ], 422);
        }

        $company = Company::findOrFail($request->company_id);

        // Verificar token de validación en cache
        $tokenKey = "validation_token_{$company->id}";
        $tokenName = $company->name;
       
        $storedToken = Cache::get($tokenKey);

        if (!$storedToken || $storedToken !== $request->validation_token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de validación inválido o expirado',
                'code' => 'INVALID_TOKEN'
            ], 422);
        }

        // Verificar que la empresa no tenga usuario asignado
        if ($company->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta empresa ya tiene un usuario registrado'
            ], 409);
        }

        try {
            \DB::beginTransaction();

            // Crear el usuario
            $user = User::create([
                'name' =>  $tokenName,
                'email' => $request->email,
                'role' => User::ROLE_COMPANY,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make($request->password),
            ]);

            // Asociar el usuario a la empresa existente
            $company->update([
                'user_id' => $user->id,                
            ]);

            // Limpiar el token de validación
            Cache::forget($tokenKey);

            \DB::commit();

            // Generar token de acceso
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente y asociado a la empresa',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' =>  $tokenName,
                        'email' => $user->email,
                        'role' => $user->role,
                        'status' => $user->status,
                    ],
                    'company' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'rif' => $company->rif,
                        'email' => $company->email,
                        'status' => $company->status,
                    ],
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el usuario: ' . $e->getMessage()
            ], 500);
        }
    }


        private function maskLicense($license)
{
    
    $length = strlen($license);

    if ($length <= 6) {
        // Si el texto es muy corto, solo muestra asteriscos
        return str_repeat('*', 8);
    }

    // Mostrar solo los últimos 6 caracteres, el resto en asteriscos
    return str_repeat('*', $length - 6) . substr($license, -6);
}

     private function generateValidationCode()
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

     private function generateValidationToken($companyId, $email,$name)
    {
        $data = [
            'company_id' => $companyId,
            'email' => $email,
            'name' => $name,
            'timestamp' => time(),
            'random' => Str::random(16)
        ];
        
        return base64_encode(json_encode($data));
    }

    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 3) {
            $maskedUsername = substr($username, 0, 1) . str_repeat('*', strlen($username) - 1);
        } else {
            $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 4) . substr($username, -2);
        }
        
        return $maskedUsername . '@' . $domain;
    }
}