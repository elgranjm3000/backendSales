<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acceso;
use App\Models\User;
use App\Models\SyncAppVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;

class AdminController extends Controller
{
    /**
     * Página de documentación de los endpoints de sincronización.
     */


    /**
     * Página de documentación de los endpoints de sincronización.
     */
    public function docs()
    {
        return view('admin.docs');
    }

    /**
     * Mostrar formulario de login.
     */
    public function loginForm(Request $request)
    {
        if (Auth::check() && in_array(Auth::user()->role->value, ['admin', 'manager'])) {
            return redirect()->route('admin.accesos');
        }

        // Evitar cache del navegador para asegurar token CSRF fresco
        return response()
            ->view('admin.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * Regenerar token CSRF para formularios expirados.
     */
    public function refreshCsrfToken(Request $request): JsonResponse
    {
        // Regenerar sesión y token
        $request->session()->regenerate();
        return response()->json(['token' => csrf_token()]);
    }

    /**
     * Procesar login.
     */
    public function login(Request $request)
    {
        // Validar CAPTCHA de Google
        $recaptchaResponse = $request->input('g-recaptcha-response');

        if (!$recaptchaResponse) {
            return back()
                ->withErrors(['captcha' => 'Por favor completa el CAPTCHA.'])
                ->onlyInput('email');
        }

        // Verificar CAPTCHA con Google
        $recaptchaSecret = env('GOOGLE_RECAPTCHA_SECRET_KEY');
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';

        $response = \Illuminate\Support\Facades\Http::asForm()->post($verifyUrl, [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse,
            'remoteip' => $request->ip(),
        ]);

        $responseData = $response->json();

        // Depuración temporal - quitar después
        \Log::info('reCAPTCHA Debug:', [
            'secret' => $recaptchaSecret ? substr($recaptchaSecret, 0, 10) . '...' : 'NULL',
            'response' => $recaptchaResponse ? substr($recaptchaResponse, 0, 20) . '...' : 'NULL',
            'google_response' => $responseData,
        ]);

        if (!$responseData['success']) {
            return back()
                ->withErrors(['captcha' => 'La verificación de CAPTCHA falló.'])
                ->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Normalizar email a minúsculas para login case-insensitive
        $credentials['email'] = strtolower($credentials['email']);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Solo permitir acceso a usuarios con rol admin o manager
            if (!in_array($user->role->value, ['admin', 'manager'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'No tienes permisos para acceder.',
                ])->onlyInput('email');
            }

            if ($user->status->value !== 'active') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Tu cuenta está inactiva. Contacta al administrador.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('admin.accesos'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden.',
        ])->onlyInput('email');
    }

    /**
     * Cerrar sesión.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Listar todos los accesos con sus API keys.
     */
    public function index(Request $request)
    {
        $query = Acceso::orderBy('nombre');

        // Búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('codigo', 'ilike', "%{$search}%")
                  ->orWhere('correo_electronico', 'ilike', "%{$search}%")
                  ->orWhere('id_fiscal', 'ilike', "%{$search}%");
            });
        }

        // Obtener emails registrados para filtros de sincronización
        $registeredEmails = Company::pluck('email')->filter()->toArray();

        // Filtro por estado de empresa
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'blocked':
                    $query->whereNotNull('blocked_at');
                    break;
                case 'active':
                    $query->whereNull('blocked_at');
                    break;
                case 'no_key':
                    $query->whereNull('api_key');
                    break;
            }
        }

        // Filtro por sincronización
        if ($request->has('sync')) {
            switch ($request->sync) {
                case 'synced':
                    $query->whereIn('correo_electronico', $registeredEmails);
                    break;
                case 'unsynced':
                    $query->whereNotNull('correo_electronico')
                          ->whereNotIn('correo_electronico', $registeredEmails);
                    break;
            }
        }

        // Paginación
        $accesos = $query->with('company.sellers')->paginate(20)->withQueryString();

        // Rol del usuario actual para control de permisos
        $currentUserRole = Auth::user()->role->value;

        // Datos para inicialización vía AJAX
        $accesosJson = json_encode([
            'accesos' => collect($accesos->items())->map(function ($acceso) use ($registeredEmails) {
                return [
                    'id' => $acceso->id,
                    'nombre' => $acceso->nombre,
                    'codigo' => $acceso->codigo,
                    'correo_electronico' => $acceso->correo_electronico,
                    'id_fiscal' => $acceso->id_fiscal,
                    'ciudad' => $acceso->ciudad,
                    'estado' => $acceso->estado,
                    'telefono' => $acceso->telefono,
                    'direccion' => $acceso->direccion,
                    'api_key' => $acceso->api_key,
                    'blocked_at' => $acceso->blocked_at?->toDateString(),
                    'is_synced' => $acceso->correo_electronico && in_array($acceso->correo_electronico, $registeredEmails),
                    'company' => $acceso->company ? [
                        'id' => $acceso->company->id,
                        'name' => $acceso->company->name,
                        'offline_token_hours' => $acceso->company->offline_token_hours ?? 24,
                        'sellers' => $acceso->company->sellers->map(fn($s) => [
                            'id' => $s->id,
                            'description' => $s->description,
                            'email' => $s->user->email ?? $s->code,
                            'mobilecheck' => (bool) $s->mobilecheck,
                        ])->toArray(),
                    ] : null,
                ];
            }),
            'registered_emails' => $registeredEmails,
            'current_user_role' => $currentUserRole,
            'pagination_html' => $accesos->links()->render(),
            'total' => $accesos->total(),
            'per_page' => $accesos->perPage(),
            'current_page' => $accesos->currentPage(),
            'last_page' => $accesos->lastPage(),
        ]);

        return view('admin.accesos', compact('accesos', 'accesosJson', 'registeredEmails', 'currentUserRole'));

    }

    /**
     * Obtener datos de acceso para edición vía AJAX (POST protegido)
     */
    public function editData(int $id): JsonResponse
    {
        $acceso = Acceso::with('company.sellers')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $acceso->id,
                'nombre' => $acceso->nombre,
                'id_fiscal' => $acceso->codigo,
                'correo_electronico' => $acceso->correo_electronico,
                'direccion' => $acceso->direccion,
                'telefono' => $acceso->telefono,
                'ciudad' => $acceso->ciudad,
                'estado' => $acceso->estado,
                'blocked_at' => $acceso->blocked_at?->toDateString(),
            ]
        ]);
    }

    /**
     * Bloquear o desbloquear un acceso.
     */
    public function toggleBlock(Request $request, $id)
    {
        $acceso = Acceso::findOrFail($id);

        if ($acceso->blocked_at) {
            // Activar empresa
            $acceso->update(['blocked_at' => null]);

            // Activar usuario relacionado por email
            if ($acceso->correo_electronico) {
                User::where('email', $acceso->correo_electronico)
                    ->update(['status' => \App\Enums\GenericStatus::ACTIVE->value]);
            }

            // Activar sellers de la company
            $user = User::where('email', $acceso->correo_electronico)->first();
            if ($user) {
                $company = \App\Models\Company::where('user_id', $user->id)->first();
                if ($company) {
                    // Obtener sellers de la company
                    $sellers = \App\Models\Seller::where('company_id', $company->id)->get();

                    foreach ($sellers as $seller) {
                        // Activar en tabla sellers
                        $seller->update(['seller_status' => \App\Enums\GenericStatus::ACTIVE->value]);

                        // Activar en tabla users
                        if ($seller->user_id) {
                            User::where('id', $seller->user_id)
                                ->update(['status' => \App\Enums\GenericStatus::ACTIVE->value]);
                        }
                    }
                }
            }

            $message = "{$acceso->nombre} ha sido activado.";
        } else {
            // Desactivar empresa
            $acceso->update(['blocked_at' => now()]);

            // Desactivar usuario relacionado por email
            if ($acceso->correo_electronico) {
                User::where('email', $acceso->correo_electronico)
                    ->update(['status' => \App\Enums\GenericStatus::INACTIVE->value]);
            }

            // Desactivar sellers de la company
            $user = User::where('email', $acceso->correo_electronico)->first();
            if ($user) {
                $company = \App\Models\Company::where('user_id', $user->id)->first();
                if ($company) {
                    // Obtener sellers de la company
                    $sellers = \App\Models\Seller::where('company_id', $company->id)->get();

                    foreach ($sellers as $seller) {
                        // Desactivar en tabla sellers
                        $seller->update(['seller_status' => \App\Enums\GenericStatus::INACTIVE->value]);

                        // Desactivar en tabla users
                        if ($seller->user_id) {
                            User::where('id', $seller->user_id)
                                ->update(['status' => \App\Enums\GenericStatus::INACTIVE->value]);
                        }
                    }
                }
            }

            $message = "{$acceso->nombre} ha sido desactivado.";
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('admin.accesos', [
            'search' => $request->search,
            'filter' => $request->filter,
            'sync' => $request->sync,
        ])->with('success', $message);
    }

    /**
     * Habilitar/deshabilitar acceso móvil de un vendedor.
     */
    public function toggleMobilecheck($sellerId)
    {
        $seller = \App\Models\Seller::findOrFail($sellerId);
        $seller->update(['mobilecheck' => !$seller->mobilecheck]);

        return response()->json([
            'success' => true,
            'mobilecheck' => $seller->mobilecheck
        ]);
    }

    /**
     * Crear una nueva empresa con API key.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'id_fiscal' => 'required|string|max:50|unique:acceso,codigo',
                'direccion' => 'nullable|string',
                'telefono' => 'nullable|string|max:50',
                'ciudad' => 'nullable|string|max:100',
                'estado' => 'nullable|string|max:100',
                'correo_electronico' => 'nullable|email|max:255|unique:acceso,correo_electronico',
            ], [
                'nombre.required' => 'El nombre de la empresa es obligatorio.',
                'id_fiscal.required' => 'El RIF es obligatorio.',
                'id_fiscal.unique' => 'Este RIF ya está registrado en otra empresa.',
                'correo_electronico.email' => 'El correo electrónico debe ser una dirección válida.',
                'correo_electronico.unique' => 'Este correo electrónico ya está registrado en otra empresa.',
            ]);

            // El código será igual al ID Fiscal (RIF)
            $validated['codigo'] = $validated['id_fiscal'];

            // Verificar que el email no exista en companies o users
            if (!empty($validated['correo_electronico'])) {
                $emailExists = Company::where('email', $validated['correo_electronico'])->exists();
                if ($emailExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El email ya está registrado en otra compañía.',
                        'errors' => ['correo_electronico' => ['Este email ya está en uso en compañia.']]
                    ], 422);
                }

                $userExists = User::where('email', $validated['correo_electronico'])->exists();
                if ($userExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El email ya está registrado como usuario.',
                        'errors' => ['correo_electronico' => ['Este email ya está en uso con un usuario.']]
                    ], 422);
                }
            }

            // Generar API key única
            $validated['api_key'] = $this->generateApiKey();

            $acceso = Acceso::create($validated);

            return response()->json([
                'success' => true,
                'message' => "Empresa {$acceso->nombre} creada exitosamente. API Key: {$acceso->api_key}"
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Actualizar un acceso existente.
     */
    public function update(Request $request, $id)
    {
        $acceso = Acceso::findOrFail($id);

        // Guardar email antiguo antes de actualizar
        $oldEmail = $acceso->correo_electronico;

        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'id_fiscal' => 'required|string|max:50|unique:acceso,codigo,' . $id,
                'direccion' => 'nullable|string',
                'telefono' => 'nullable|string|max:50',
                'ciudad' => 'nullable|string|max:100',
                'estado' => 'nullable|string|max:100',
                'correo_electronico' => 'nullable|email|max:255|unique:acceso,correo_electronico,' . $id,
            ], [
                'nombre.required' => 'El nombre de la empresa es obligatorio.',
                'id_fiscal.required' => 'El RIF es obligatorio.',
                'id_fiscal.unique' => 'Este RIF ya está registrado en otra empresa.',
                'correo_electronico.email' => 'El correo electrónico debe ser una dirección válida.',
                'correo_electronico.unique' => 'Este correo electrónico ya está registrado en otra empresa.',
            ]);

            // El código será igual al ID Fiscal (RIF)
            $validated['codigo'] = $validated['id_fiscal'];

            // Verificar que el email no exista en companies o users (excluyendo el email actual)
            if (!empty($validated['correo_electronico']) && $validated['correo_electronico'] !== $oldEmail) {
                $emailExists = \App\Models\Company::where('email', $validated['correo_electronico'])
                    ->where('email', '!=', $oldEmail)
                    ->exists();
                if ($emailExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El email ya está registrado en otra compañía.',
                        'errors' => ['correo_electronico' => ['Este email ya está en uso en la tabla companies.']]
                    ], 422);
                }

                $userExists = \App\Models\User::where('email', $validated['correo_electronico'])
                    ->where('email', '!=', $oldEmail)
                    ->exists();
                if ($userExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El email ya está registrado como usuario.',
                        'errors' => ['correo_electronico' => ['Este email ya está en uso en la tabla users.']]
                    ], 422);
                }
            }

            // Mantener la API key existente (no regenerar)
            unset($validated['api_key']);

            $acceso->update($validated);

            // Si el email cambió, actualizar en companies
            if (isset($validated['correo_electronico']) && $validated['correo_electronico'] !== $oldEmail) {
                $company = \App\Models\Company::where('email', $oldEmail)->first();
                if ($company) {
                    $company->update([
                        'email' => $validated['correo_electronico'],
                        'name' => $validated['nombre']
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Empresa {$acceso->nombre} actualizada exitosamente."
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar un API key único.
     */
    private function generateApiKey(): string
    {
        do {
            $apiKey = 'chr_' . bin2hex(random_bytes(20));
        } while (Acceso::where('api_key', $apiKey)->exists());

        return $apiKey;
    }

    /**
     * Actualizar horas offline permitidas por empresa
     */
    public function updateOfflineHours(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'offline_token_hours' => 'required|integer|min:1|max:720',
            ], [
                'offline_token_hours.required' => 'Las horas offline son requeridas',
                'offline_token_hours.min' => 'Mínimo 1 hora',
                'offline_token_hours.max' => 'Máximo 720 horas (30 días)',
            ]);

            $company = Company::findOrFail($id);
            $company->update(['offline_token_hours' => $validated['offline_token_hours']]);

            return response()->json([
                'success' => true,
                'message' => "Horas offline actualizadas a {$validated['offline_token_hours']}hs",
                'offline_token_hours' => $company->offline_token_hours,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

     public function usuarios(Request $request)
      {
          $query = User::whereIn('role', ['admin', 'manager', 'cajero'])->orderBy('name');

          // Búsqueda
          if ($request->has('search')) {
              $search = $request->search;
              $query->where(function ($q) use ($search) {
                  $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
              });
          }

          // Filtro por rol
          if ($request->has('role') && $request->role) {
              $query->where('role', $request->role);
          }

          // Filtro por estado
          if ($request->has('status') && $request->status) {
              $query->where('status', $request->status);
          }

          $usuarios = $query->paginate(20)->withQueryString();
          return view('admin.usuarios', compact('usuarios'));
      }

      public function createUsuario()
      {
          $roles = ['admin', 'manager', 'cajero'];
          return view('admin.usuario-form', compact('roles'));
      }

      public function storeUsuario(Request $request)
      {
          try {
              $data = $request->validate([
                  'name' => 'required|string|max:255',
                  'email' => 'required|email|max:255|unique:users,email',
                  'phone' => 'nullable|string|max:20',
                  'password' => 'required|string|min:6',
                  'role' => 'required|in:admin,manager,cajero',
                  'status' => 'required|in:active,inactive',
              ], [
                  'email.unique' => 'Este email ya lo tiene un usuario.',
                  'email.required' => 'El email es obligatorio.',
                  'email.email' => 'El email debe ser una dirección válida.',
                  'name.required' => 'El nombre es obligatorio.',
                  'password.required' => 'La contraseña es obligatoria.',
                  'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                  'role.required' => 'El rol es obligatorio.',
                  'role.in' => 'El rol debe ser admin, super administrador o sincronizador.',
                  'status.required' => 'El estado es obligatorio.',
                  'status.in' => 'El estado debe ser activo o inactivo.',
              ]);

              $data['email'] = strtolower($data['email']);
              $data['password'] = bcrypt($data['password']);

              // Verificar que el email no exista en acceso o companies
              $accesoExists = \App\Models\Acceso::where('correo_electronico', $data['email'])->exists();
              if ($accesoExists) {
                  if ($request->expectsJson()) {
                      return response()->json([
                          'success' => false,
                          'message' => 'El email ya está registrado en una empresa.',
                          'errors' => ['email' => ['Este email ya está en uso en la tabla acceso (empresas).']]
                      ], 422);
                  }
                  return redirect()->back()->withInput()->withErrors([
                      'email' => 'Este email ya está en uso en la tabla acceso (empresas).'
                  ]);
              }

              $companyExists = \App\Models\Company::where('email', $data['email'])->exists();
              if ($companyExists) {
                  if ($request->expectsJson()) {
                      return response()->json([
                          'success' => false,
                          'message' => 'El email ya está registrado en una compañía.',
                          'errors' => ['email' => ['Este email ya está en uso en la tabla companies.']]
                      ], 422);
                  }
                  return redirect()->back()->withInput()->withErrors([
                      'email' => 'Este email ya está en uso en la tabla companies.'
                  ]);
              }

              $user = User::create($data);

              // Si es petición AJAX (del modal)
              if ($request->expectsJson()) {
                  return response()->json([
                      'success' => true,
                      'message' => "Usuario {$user->name} creado correctamente."
                  ]);
              }

              return redirect()->route('admin.usuarios')->with('success', 'Usuario creado correctamente.');

          } catch (\Illuminate\Validation\ValidationException $e) {
              if ($request->expectsJson()) {
                  return response()->json([
                      'success' => false,
                      'errors' => $e->errors()
                  ], 422);
              }
              throw $e;
          }
      }

      public function editUsuario($id)
      {
          $user = User::findOrFail($id);
          $roles = ['admin', 'manager', 'cajero'];
          return view('admin.usuario-form', compact('user', 'roles'));
      }

      public function updateUsuario(Request $request, $id)
      {
          $user = User::findOrFail($id);

          try {
              $data = $request->validate([
                  'name' => 'required|string|max:255',
                  'email' => 'required|email|max:255|unique:users,email,'.$id,
                  'phone' => 'nullable|string|max:20',
                  'password' => 'nullable|string|min:6',
                  'role' => 'required|in:admin,manager,cajero',
                  'status' => 'required|in:active,inactive',
              ], [
                  'email.unique' => 'Este email ya lo tiene un usuario.',
                  'email.required' => 'El email es obligatorio.',
                  'email.email' => 'El email debe ser una dirección válida.',
                  'name.required' => 'El nombre es obligatorio.',
                  'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                  'role.required' => 'El rol es obligatorio.',
                  'role.in' => 'El rol debe ser admin, super administrador o sincronizador.',
                  'status.required' => 'El estado es obligatorio.',
                  'status.in' => 'El estado debe ser activo o inactivo.',
              ]);

              $data['email'] = strtolower($data['email']);

              if (empty($data['password'])) {
                  unset($data['password']);
              } else {
                  $data['password'] = bcrypt($data['password']);
              }

              // Verificar que el email no exista en acceso o companies (solo si el email cambió)
              if ($data['email'] !== $user->email) {
                  $accesoExists = \App\Models\Acceso::where('correo_electronico', $data['email'])->exists();
                  if ($accesoExists) {
                      if ($request->expectsJson()) {
                          return response()->json([
                              'success' => false,
                              'message' => 'El email ya está registrado en una empresa.',
                              'errors' => ['email' => ['Este email ya está en uso en la tabla acceso (empresas).']]
                          ], 422);
                      }
                      return redirect()->back()->withInput()->withErrors([
                          'email' => 'Este email ya está en uso en la tabla acceso (empresas).'
                      ]);
                  }

                  $companyExists = \App\Models\Company::where('email', $data['email'])->exists();
                  if ($companyExists) {
                      if ($request->expectsJson()) {
                          return response()->json([
                              'success' => false,
                              'message' => 'El email ya está registrado en una compañía.',
                              'errors' => ['email' => ['Este email ya está en uso en la tabla companies.']]
                          ], 422);
                      }
                      return redirect()->back()->withInput()->withErrors([
                          'email' => 'Este email ya está en uso en la tabla companies.'
                      ]);
                  }
              }

              $user->update($data);

              // Si es petición AJAX (del modal)
              if ($request->expectsJson()) {
                  return response()->json([
                      'success' => true,
                      'message' => "Usuario {$user->name} actualizado correctamente."
                  ]);
              }

              return redirect()->route('admin.usuarios')->with('success', 'Usuario actualizado correctamente.');

          } catch (\Illuminate\Validation\ValidationException $e) {
              if ($request->expectsJson()) {
                  return response()->json([
                      'success' => false,
                      'errors' => $e->errors()
                  ], 422);
              }
              throw $e;
          }
      }

      public function destroyUsuario(Request $request, $id)
      {
          $user = User::findOrFail($id);

          if ($user->id === auth()->id()) {
              if ($request->expectsJson()) {
                  return response()->json([
                      'success' => false,
                      'message' => 'No puedes eliminarte a ti mismo.'
                  ], 403);
              }
              return redirect()->route('admin.usuarios')->with('error', 'No puedes eliminarte a ti mismo.');
          }

          $nombre = $user->name;
          $user->delete();

          if ($request->expectsJson()) {
              return response()->json([
                  'success' => true,
                  'message' => "Usuario {$nombre} eliminado correctamente."
              ]);
          }

          return redirect()->route('admin.usuarios')->with('success', 'Usuario eliminado correctamente.');
      }

      /**
       * Obtener datos de usuario para edición vía AJAX (POST protegido)
       */
      public function editDataUsuario(int $id): JsonResponse
      {
          $user = User::findOrFail($id);

          return response()->json([
              'success' => true,
              'data' => [
                  'id' => $user->id,
                  'name' => $user->name,
                  'email' => $user->email,
                  'phone' => $user->phone,
                  'role' => $user->role->value,
                  'status' => $user->status->value,
              ]
          ]);
      }

      /**
       * Eliminar acceso y todos sus datos relacionados (users, company, sellers)
       */
      public function destroy(Request $request, $id): JsonResponse
      {
          // Solo usuarios con rol manager pueden eliminar
          if (Auth::user()->role->value !== 'manager') {
              return response()->json([
                  'success' => false,
                  'message' => 'No tienes permisos para eliminar empresas.'
              ], 403);
          }

          $acceso = Acceso::findOrFail($id);

          if (!$acceso->correo_electronico) {
              return response()->json([
                  'success' => false,
                  'message' => 'El acceso no tiene email asociado. No se puede eliminar de forma completa.'
              ], 422);
          }

          $email = $acceso->correo_electronico;
          $nombre = $acceso->nombre;

          try {
              // 1. Buscar y eliminar vendedores asociados a la compañía
              $user = User::where('email', $email)->first();
              if ($user) {
                  $company = Company::where('user_id', $user->id)->first();
                  if ($company) {
                      // Eliminar sellers de la compañía
                      $sellers = \App\Models\Seller::where('company_id', $company->id)->get();
                      foreach ($sellers as $seller) {
                          // Eliminar usuario asociado al seller
                          if ($seller->user_id) {
                              User::where('id', $seller->user_id)->delete();
                          }
                          // Eliminar seller
                          $seller->delete();
                      }
                      // Eliminar compañía
                      $company->delete();
                  }
                  // Eliminar el usuario principal
                  $user->delete();
              }

              // 2. Eliminar el acceso
              $acceso->delete();

              return response()->json([
                  'success' => true,
                  'message' => "{$nombre} y todos sus datos asociados han sido eliminados correctamente."
              ]);

          } catch (\Throwable $e) {
              return response()->json([
                  'success' => false,
                  'message' => 'Error al eliminar: ' . $e->getMessage()
              ], 500);
          }
      }

      /**
       * Buscar accesos vía AJAX (búsqueda dinámica)
       * GET /admin/accesos/search?search=&filter=&sync=
       */
      public function searchAccesosJson(Request $request): JsonResponse
      {
          $query = Acceso::orderBy('nombre');

          // Búsqueda
          if ($request->has('search')) {
              $search = $request->search;
              $query->where(function ($q) use ($search) {
                  $q->where('nombre', 'ilike', "%{$search}%")
                    ->orWhere('codigo', 'ilike', "%{$search}%")
                    ->orWhere('correo_electronico', 'ilike', "%{$search}%")
                    ->orWhere('id_fiscal', 'ilike', "%{$search}%");
              });
          }

          // Emails registrados para filtro de sincronización
          $registeredEmails = Company::pluck('email')->filter()->toArray();

          // Filtro por estado
          if ($request->has('filter')) {
              switch ($request->filter) {
                  case 'blocked':
                      $query->whereNotNull('blocked_at');
                      break;
                  case 'active':
                      $query->whereNull('blocked_at');
                      break;
                  case 'no_key':
                      $query->whereNull('api_key');
                      break;
              }
          }

          // Filtro por sincronización
          if ($request->has('sync')) {
              switch ($request->sync) {
                  case 'synced':
                      $query->whereIn('correo_electronico', $registeredEmails);
                      break;
                  case 'unsynced':
                      $query->whereNotNull('correo_electronico')
                            ->whereNotIn('correo_electronico', $registeredEmails);
                      break;
              }
          }

          $perPage = $request->integer('per_page', 20);
          $accesos = $query->with('company.sellers')->paginate($perPage);

          // Transformar datos para JSON
          $data = collect($accesos->items())->map(function ($acceso) use ($registeredEmails) {
              return [
                  'id' => $acceso->id,
                  'nombre' => $acceso->nombre,
                  'codigo' => $acceso->codigo,
                  'correo_electronico' => $acceso->correo_electronico,
                  'id_fiscal' => $acceso->id_fiscal,
                  'ciudad' => $acceso->ciudad,
                  'estado' => $acceso->estado,
                  'telefono' => $acceso->telefono,
                  'direccion' => $acceso->direccion,
                  'api_key' => $acceso->api_key,
                  'blocked_at' => $acceso->blocked_at?->toDateString(),
                  'is_synced' => $acceso->correo_electronico && in_array($acceso->correo_electronico, $registeredEmails),
                  'company' => $acceso->company ? [
                      'id' => $acceso->company->id,
                      'name' => $acceso->company->name,
                      'offline_token_hours' => $acceso->company->offline_token_hours ?? 24,
                      'sellers' => $acceso->company->sellers->map(fn($s) => [
                          'id' => $s->id,
                          'description' => $s->description,
                          'email' => $s->user->email ?? $s->code,
                          'mobilecheck' => (bool) $s->mobilecheck,
                      ])->toArray(),
                  ] : null,
              ];
          });

          $currentUserRole = Auth::user()->role->value;

          return response()->json([
              'success' => true,
              'data' => $data,
              'total' => $accesos->total(),
              'per_page' => $accesos->perPage(),
              'current_page' => $accesos->currentPage(),
              'last_page' => $accesos->lastPage(),
              'pagination_html' => $accesos->links()->render(),
              'registered_emails' => $registeredEmails,
              'current_user_role' => $currentUserRole,
          ]);
      }

      // =========================================================================
      // SYNC APP VERSIONS
      // =========================================================================

      /**
       * Listar versiones de app para sincronización
       */
      public function syncVersions()
      {
          $versions = SyncAppVersion::orderBy('created_at', 'desc')->get();
          return view('admin.sync-versions', compact('versions'));
      }

      /**
       * Crear nueva versión de app
       */
      public function storeSyncVersion(Request $request)
      {
          $request->validate([
              'typeapp' => 'required|in:mobile,sincronizador,chrystal',
              'version' => 'required|string|max:20|unique:sync_app_versions,version,NULL,id,typeapp,' . $request->typeapp,
              'status' => 'required|in:active,inactive',
              'notes' => 'nullable|string|max:500',
          ], [
              'typeapp.required' => 'El tipo de app es obligatorio.',
              'typeapp.in' => 'El tipo debe ser mobile, sincronizador o chrystal.',
              'version.required' => 'La versión es obligatoria.',
              'version.unique' => 'Esta versión ya existe para este tipo de app.',
              'status.required' => 'El estado es obligatorio.',
              'status.in' => 'El estado debe ser active o inactive.',
          ]);

          SyncAppVersion::create([
              'typeapp' => $request->typeapp,
              'version' => $request->version,
              'status' => $request->status,
              'notes' => $request->notes,
          ]);

          return response()->json([
              'success' => true,
              'message' => 'Versión creada correctamente.'
          ]);
      }

      /**
       * Actualizar estado de versión
       */
      public function updateSyncVersion(Request $request, $id)
      {
          $version = SyncAppVersion::findOrFail($id);

          $request->validate([
              'typeapp' => 'required|in:mobile,sincronizador,chrystal',
              'status' => 'required|in:active,inactive',
              'notes' => 'nullable|string|max:500',
          ], [
              'typeapp.required' => 'El tipo de app es obligatorio.',
              'typeapp.in' => 'El tipo debe ser mobile, sincronizador o chrystal.',
              'status.required' => 'El estado es obligatorio.',
              'status.in' => 'El estado debe ser active o inactive.',
          ]);

          $version->update([
              'typeapp' => $request->typeapp,
              'status' => $request->status,
              'notes' => $request->notes ?? $version->notes,
          ]);

          return response()->json([
              'success' => true,
              'message' => 'Versión actualizada correctamente.'
          ]);
      }

      /**
       * Eliminar versión
       */
      public function destroySyncVersion($id)
      {
          $version = SyncAppVersion::findOrFail($id);
          $version->delete();

          return response()->json([
              'success' => true,
              'message' => 'Versión eliminada correctamente.'
          ]);
      }

      /**
       * Obtener datos de versión para edición (AJAX)
       */
      public function editSyncVersionData($id): JsonResponse
      {
          $version = SyncAppVersion::findOrFail($id);

          return response()->json([
              'success' => true,
              'data' => [
                  'id' => $version->id,
                  'typeapp' => $version->typeapp,
                  'version' => $version->version,
                  'status' => $version->status,
                  'notes' => $version->notes,
              ]
          ]);
      }

}
