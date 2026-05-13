<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SeniatService;
use Illuminate\Http\Request;

class SeniatController extends Controller
{
    protected $seniatService;
    protected $captchaSolver;

    public function __construct(SeniatService $seniatService)
    {
        $this->seniatService = $seniatService;
        $this->captchaSolver = new \App\Services\CaptchaSolverService();
    }

    /**
     * Consultar RIF en el SENIAT
     * GET /api/seniat/consultar?rif=J-12345678-9
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultar(Request $request)
    {
        $request->validate([
            'rif' => 'required|string|max:20'
        ]);

        $rif = $request->input('rif');

        // Consultar al SENIAT
        $resultado = $this->seniatService->consultarRif($rif);

        return response()->json($resultado, 200);
    }

    /**
     * Validar formato de RIF (sin consultar al SENIAT)
     * GET /api/seniat/validar-formato?rif=J-12345678-9
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validarFormato(Request $request)
    {
        $request->validate([
            'rif' => 'required|string|max:20'
        ]);

        $rif = $request->input('rif');

        $resultado = $this->seniatService->validarFormato($rif);

        return response()->json($resultado, 200);
    }

    /**
     * Debug: Ver respuesta cruda del SENIAT
     * GET /api/seniat/debug?rif=J123456789
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function debug(Request $request)
    {
        $request->validate([
            'rif' => 'required|string|max:20'
        ]);

        $rif = $request->input('rif');

        // Obtener respuesta cruda del SENIAT
        $resultado = $this->seniatService->debugResponse($rif);

        return response()->json($resultado, 200);
    }

    /**
     * Debug: Ver CAPTCHA y resultado del OCR
     * GET /api/seniat/debug-captcha
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function debugCaptcha(Request $request)
    {
        // Obtener CAPTCHA y resultado del OCR
        $resultado = $this->seniatService->debugCaptcha();

        return response()->json($resultado, 200);
    }

    /**
     * Consultar RIF con reintentos automáticos
     * GET /api/seniat/consultar-reintentos?rif=J123456789&max_reintentos=3
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultarConReintentos(Request $request)
    {
        $request->validate([
            'rif' => 'required|string|max:20',
            'max_reintentos' => 'nullable|integer|min:1|max:100'
        ]);

        $rif = $request->input('rif');
        $maxReintentos = $request->input('max_reintentos', 3);

        $intentos = 0;
        $errores = [];

        while ($intentos < $maxReintentos) {
            $intentos++;

            $resultado = $this->seniatService->consultarRif($rif);

            // Si fue exitoso (encontrado o no encontrado), retornar
            if ($resultado['success'] === true) {
                $resultado['intentos'] = $intentos;
                return response()->json($resultado, 200);
            }

            // Guardar el error
            $errores[] = [
                'intento' => $intentos,
                'mensaje' => $resultado['message']
            ];

            // Si no es el último intento, esperar un momento
            if ($intentos < $maxReintentos) {
                usleep(500000); // Esperar 0.5 segundos
            }
        }

        // Todos los intentos fallaron
        return response()->json([
            'success' => false,
            'valid' => false,
            'message' => "No se pudo consultar el RIF después de $maxReintentos intentos",
            'rif' => $rif,
            'intentos' => $intentos,
            'errores' => $errores
        ], 200);
    }

    /**
     * Consultar saldo de 2Captcha
     * GET /api/seniat/captcha-saldo
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCaptchaSaldo()
    {
        try {
            $captchaSolver = new \App\Services\CaptchaSolverService();
            $saldo = $captchaSolver->getBalance();

            return response()->json([
                'success' => $saldo !== null,
                'saldo' => $saldo,
                'moneda' => 'USD',
                'servicio' => '2Captcha',
                'message' => $saldo !== null
                    ? "Saldo actual: \${$saldo} USD"
                    : 'No se pudo obtener el saldo. Verifica tu API key.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener imagen del CAPTCHA del SENIAT
     * GET /api/seniat/captcha
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerCaptcha()
    {
        try {
            $captchaData = $this->seniatService->obtenerCaptchaImagen();

            if (!$captchaData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el CAPTCHA del SENIAT'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'captcha_base64' => $captchaData,  // Imagen en base64 para mostrar en frontend
                'formato' => 'image/jpeg',
                'message' => 'CAPTCHA obtenido exitosamente. Muestra esta imagen al usuario y pídele que ingrese el código.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar RIF con CAPTCHA ingresado manualmente por el usuario
     * POST /api/seniat/consultar-manual
     * Body: { "rif": "J123456789", "captcha": "ABC123" }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultarConCaptchaManual(Request $request)
    {
        $request->validate([
            'rif' => 'required|string|max:20',
            'captcha' => 'required|string|max:10'
        ]);

        try {
            $rif = $request->input('rif');
            $captcha = strtoupper(trim($request->input('captcha')));

            $resultado = $this->seniatService->consultarRifConCaptchaManual($rif, $captcha);

            if ($resultado['success']) {
                return response()->json($resultado, 200);
            } else {
                return response()->json($resultado, 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
