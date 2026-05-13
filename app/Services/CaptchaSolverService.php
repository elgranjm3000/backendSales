<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptchaSolverService
{
    private $apiKey;
    private $baseUrl = 'http://2captcha.com';

    public function __construct()
    {
        $this->apiKey = config('services.twocaptcha.api_key', env('TWOCAPTCHA_API_KEY', ''));
    }

    /**
     * Resolver CAPTCHA usando 2Captcha
     *
     * @param string $imageUrl URL de la imagen del CAPTCHA
     * @return string|null El texto del CAPTCHA resuelto o null si falló
     */
    public function solveCaptcha(string $imageUrl): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('2Captcha API key no configurada');
            return null;
        }

        try {
            // Paso 1: Descargar la imagen del CAPTCHA
            $imageData = Http::timeout(10)->get($imageUrl)->body();
            $base64Image = base64_encode($imageData);

            // Paso 2: Enviar el CAPTCHA a 2Captcha
            $captchaId = $this->sendTo2Captcha($base64Image);

            if (!$captchaId) {
                return null;
            }

            Log::info('CAPTCHA enviado a 2Captcha', ['captcha_id' => $captchaId]);

            // Paso 3: Esperar la solución (polling)
            $solution = $this->pollForSolution($captchaId);

            if ($solution) {
                Log::info('CAPTCHA resuelto por 2Captcha', [
                    'captcha_id' => $captchaId,
                    'solution' => $solution
                ]);
            }

            return $solution;

        } catch (\Exception $e) {
            Log::error('Error resolviendo CAPTCHA con 2Captcha: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Enviar CAPTCHA a 2Captcha
     *
     * @param string $base64Image Imagen en base64
     * @return string|null ID del CAPTCHA o null si falló
     */
    private function sendTo2Captcha(string $base64Image): ?string
    {
        $response = Http::asForm()->timeout(30)->post($this->baseUrl . '/in.php', [
            'key' => $this->apiKey,
            'method' => 'base64',
            'body' => $base64Image,
            'json' => 1,
        ]);

        $result = json_decode($response->body(), true);

        if (isset($result['status']) && $result['status'] === 1) {
            return $result['request'];
        }

        if (isset($result['request'])) {
            Log::error('Error 2Captcha enviando CAPTCHA', [
                'error' => $result['request']
            ]);
        }

        return null;
    }

    /**
     * Esperar la solución del CAPTCHA (polling)
     *
     * @param string $captchaId ID del CAPTCHA
     * @param int $maxAttempts Máximo número de intentos
     * @param int $delay Segundos a esperar entre intentos
     * @return string|null Solución del CAPTCHA o null si expiró
     */
    private function pollForSolution(string $captchaId, int $maxAttempts = 20, int $delay = 3): ?string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            // Esperar antes del primer intento (2Captcha necesita tiempo para resolver)
            if ($i > 0) {
                sleep($delay);
            }

            $response = Http::asForm()->timeout(10)->get($this->baseUrl . '/res.php', [
                'key' => $this->apiKey,
                'action' => 'get',
                'id' => $captchaId,
                'json' => 1,
            ]);

            $result = json_decode($response->body(), true);

            if (isset($result['status'])) {
                if ($result['status'] === 1) {
                    // CAPTCHA resuelto
                    return $result['request'];
                } elseif ($result['request'] === 'CAPCHA_NOT_READY') {
                    // Seguir esperando
                    continue;
                }
            }

            if (isset($result['request'])) {
                Log::warning('Error en polling 2Captcha', [
                    'captcha_id' => $captchaId,
                    'attempt' => $i + 1,
                    'error' => $result['request']
                ]);
            }
        }

        Log::error('CAPTCHA no resuelto después de todos los intentos', [
            'captcha_id' => $captchaId,
            'max_attempts' => $maxAttempts
        ]);

        return null;
    }

    /**
     * Obtener saldo de la cuenta 2Captcha
     *
     * @return float|null Saldo en USD o null si falló
     */
    public function getBalance(): ?float
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::asForm()->timeout(10)->get($this->baseUrl . '/res.php', [
                'key' => $this->apiKey,
                'action' => 'getbalance',
                'json' => 1,
            ]);

            $result = json_decode($response->body(), true);

            if (isset($result['status']) && $result['status'] === 1) {
                return (float) $result['request'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error obteniendo saldo 2Captcha: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Reportar CAPTCHA incorrecto (para obtener reembolso)
     *
     * @param string $captchaId ID del CAPTCHA
     * @return bool
     */
    public function reportIncorrect(string $captchaId): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->get($this->baseUrl . '/res.php', [
                'key' => $this->apiKey,
                'action' => 'reportbad',
                'id' => $captchaId,
            ]);

            return strpos($response->body(), 'OK_REPORT') !== false;

        } catch (\Exception $e) {
            Log::error('Error reportando CAPTCHA incorrecto: ' . $e->getMessage());
            return false;
        }
    }
}
