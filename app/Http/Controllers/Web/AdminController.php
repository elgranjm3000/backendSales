<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Acceso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
    public function loginForm()
    {
        if (Auth::check() && in_array(Auth::user()->role->value, ['admin', 'manager'])) {
            return redirect()->route('admin.accesos');
        }
        return view('admin.login');
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
        return redirect('/');
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

        // Paginación
        $accesos = $query->with('company.sellers')->paginate(20)->withQueryString();

        return view('admin.accesos', compact('accesos'));

    }

    /**
     * Mostrar formulario de edición de un acceso.
     */
    public function edit($id, Request $request)
    {
        $accesoEdit = Acceso::with('company.sellers')->findOrFail($id);

        $query = Acceso::orderBy('nombre');

        // Mantener los filtros actuales
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ilike', "%{$search}%")
                  ->orWhere('codigo', 'ilike', "%{$search}%")
                  ->orWhere('correo_electronico', 'ilike', "%{$search}%")
                  ->orWhere('id_fiscal', 'ilike', "%{$search}%");
            });
        }

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

        $accesos = $query->with('company.sellers')->paginate(20)->withQueryString();
        $editMode = true;


        return view('admin.accesos', compact('accesos', 'accesoEdit', 'editMode'));
    }

    /**
     * Bloquear o desbloquear un acceso.
     */
    public function toggleBlock($id)
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

        return redirect()->route('admin.accesos')->with('success', $message);
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_fiscal' => 'required|string|max:50|unique:acceso,codigo',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string|max:50',
            'ciudad' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:100',
            'correo_electronico' => 'nullable|email|max:255',
        ]);

        // El código será igual al ID Fiscal (RIF)
        $validated['codigo'] = $validated['id_fiscal'];

        // Generar API key única
        $validated['api_key'] = $this->generateApiKey();

        $acceso = Acceso::create($validated);

        return redirect()->route('admin.accesos')->with('success', "Empresa {$acceso->nombre} creada exitosamente. API Key: {$acceso->api_key}");
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
                'correo_electronico.unique' => 'Este correo electrónico ya está registrado en otra empresa.',
                'id_fiscal.unique' => 'Este RIF ya está registrado en otra empresa.',
            ]);

            // El código será igual al ID Fiscal (RIF)
            $validated['codigo'] = $validated['id_fiscal'];

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

            return redirect()->route('admin.accesos')->with('success', "Empresa {$acceso->nombre} actualizada exitosamente.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Redirigir a la ruta de edición con los errores
            return redirect()->route('admin.accesos.edit', $id)
                ->withErrors($e->errors())
                ->withInput();
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
}
