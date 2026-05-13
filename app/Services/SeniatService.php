<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SeniatService
{
    protected $baseUrl = 'http://contribuyente.seniat.gob.ve/BuscaRif/BuscaRif.jsp';
    protected $captchaUrl = 'http://contribuyente.seniat.gob.ve/BuscaRif/Captcha.jpg';

    protected $captchaSolver;

    public function __construct(CaptchaSolverService $captchaSolver = null)
    {
        $this->captchaSolver = $captchaSolver ?? new CaptchaSolverService();
    }

    /**
     * Consultar RIF en el SENIAT con OCR del CAPTCHA
     *
     * @param string $rif Formato: J123456789 o V123456789
     * @return array
     */
    public function consultarRif(string $rif): array
    {
        try {
            // Normalizar el RIF
            $rif = strtoupper(trim($rif));

            // Validar formato básico del RIF antes de consultar
            if (!$this->validarFormatoRif($rif)) {
                return [
                    'success' => false,
                    'valid' => false,
                    'message' => 'Formato de RIF inválido. Use: J123456789 o V123456789 (SIN guiones)',
                    'nombre' => null,
                    'rif' => $rif
                ];
            }

            // Verificar cache primero
            $cacheKey = 'seniat_rif_' . md5($rif);
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Paso 1: Intentar resolver CAPTCHA con 2Captcha (tasa de éxito: 95-99%)
            \Log::info('Intentando resolver CAPTCHA con 2Captcha...');
            $captchaCode = $this->captchaSolver->solveCaptcha($this->captchaUrl);

            // Paso 2: Si 2Captcha falló o no está configurado, usar Tesseract OCR local
            if (!$captchaCode) {
                \Log::info('2Captcha no disponible o falló, usando Tesseract OCR local');

                $captchaPath = $this->descargarCaptcha();
                $captchaCode = $this->leerCaptchaConOCR($captchaPath);

                // Limpiar imagen temporal
                if (file_exists($captchaPath)) {
                    unlink($captchaPath);
                }
            } else {
                \Log::info('CAPTCHA resuelto exitosamente con 2Captcha', [
                    'codigo' => $captchaCode
                ]);
            }

            // Validar que tengamos un CAPTCHA válido
            if (!$captchaCode || strlen($captchaCode) < 4) {
                return [
                    'success' => false,
                    'valid' => false,
                    'message' => 'No se pudo leer el CAPTCHA. Intente nuevamente.',
                    'nombre' => null,
                    'rif' => $rif
                ];
            }

            // Paso 3: Enviar formulario con el CAPTCHA resuelto
            $formData = [
                'p_rif' => $rif,
                'p_cedula' => '',  // Campo vacío (el SENIAT lo requiere)
                'codigo' => $captchaCode
            ];

            \Log::info('Enviando formulario al SENIAT', [
                'rif' => $rif,
                'rif_length' => strlen($rif),
                'captcha' => $captchaCode,
                'form_data' => $formData
            ]);

            $response = Http::timeout(15)
                ->asForm()
                ->post($this->baseUrl, $formData);

            $html = $response->body();

            // Convertir de WINDOWS-1252 a UTF-8
            $html = mb_convert_encoding($html, 'UTF-8', 'WINDOWS-1252');

            // Log para debug
            \Log::info('SENIAT - RIF: ' . $rif . ', CAPTCHA: ' . $captchaCode, [
                'response_length' => strlen($html)
            ]);

            // Paso 4: Extraer nombre de la respuesta
            $nombre = $this->extraerNombreDeRespuesta($html);

            // Verificar si el SENIAT rechazó el CAPTCHA
            // El mensaje puede aparecer con o sin tildes, y con codificación HTML
            if (preg_match('/EL\s+c[oó]digo\s+no\s+coincide\s+con\s+la\s+imagen/ui', $html)) {
                \Log::info('CAPTCHA rechazado por el SENIAT', ['rif' => $rif]);
                return [
                    'success' => false,
                    'valid' => false,
                    'message' => 'El CAPTCHA expiró o fue incorrecto. Por favor, intente nuevamente.',
                    'nombre' => null,
                    'rif' => $rif
                ];
            }

            if ($nombre && $nombre !== '' && strlen($nombre) > 2 && $nombre !== '&nbsp;') {
                $result = [
                    'success' => true,
                    'valid' => true,
                    'message' => 'RIF encontrado',
                    'nombre' => $this->limpiarNombre($nombre),
                    'rif' => $rif
                ];

                // Guardar en cache por 7 días
                Cache::put($cacheKey, $result, now()->addDays(7));

                return $result;
            }

            // RIF no encontrado
            $result = [
                'success' => true,
                'valid' => false,
                'message' => 'RIF no encontrado en el SENIAT',
                'nombre' => null,
                'rif' => $rif
            ];

            Cache::put($cacheKey, $result, now()->addHours(1));

            return $result;

        } catch (\Exception $e) {
            \Log::error('Error consultando RIF en SENIAT: ' . $e->getMessage(), [
                'rif' => $rif,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'valid' => false,
                'message' => 'Error al consultar el SENIAT: ' . $e->getMessage(),
                'nombre' => null,
                'rif' => $rif
            ];
        }
    }

    /**
     * Descargar imagen del CAPTCHA
     */
    private function descargarCaptcha(): string
    {
        $captchaContent = Http::timeout(10)->get($this->captchaUrl)->body();

        // Guardar temporalmente
        $tempPath = storage_path('app/temp/captcha_' . time() . '.jpg');
        file_put_contents($tempPath, $captchaContent);

        return $tempPath;
    }

    /**
     * Preprocesar imagen para mejorar OCR
     * - Convertir a escala de grises
     * - Aumentar contraste
     * - Eliminar ruido
     */
    private function preprocesarImagen(string $imagePath): string
    {
        try {
            // Crear imagen desde archivo
            $image = imagecreatefromjpeg($imagePath);
            if (!$image) {
                \Log::warning('No se pudo crear imagen desde JPEG, usando imagen original');
                return $imagePath;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Convertir a escala de grises
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $rgb = imagecolorat($image, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    // Fórmula de luminancia
                    $gray = round(0.299 * $r + 0.587 * $g + 0.114 * $b);

                    // Aplicar umbralización (threshold) para alto contraste
                    // Si es > 180 = blanco, si no = negro
                    $threshold = 180;
                    $binary = $gray > $threshold ? 255 : 0;

                    $color = imagecolorallocate($image, $binary, $binary, $binary);
                    imagesetpixel($image, $x, $y, $color);
                }
            }

            // Guardar imagen procesada
            $processedPath = preg_replace('/\.jpg$/', '_processed.jpg', $imagePath);
            imagejpeg($image, $processedPath, 100);
            imagedestroy($image);

            // Eliminar imagen original
            if (file_exists($imagePath) && $imagePath !== $processedPath) {
                unlink($imagePath);
            }

            \Log::info('Imagen preprocesada', [
                'original' => $imagePath,
                'processed' => $processedPath
            ]);

            return $processedPath;

        } catch (\Exception $e) {
            \Log::error('Error preprocesando imagen: ' . $e->getMessage());
            return $imagePath;
        }
    }

    /**
     * Leer CAPTCHA usando Tesseract OCR directamente
     */
    private function leerCaptchaConOCR(string $imagePath): string
    {
        try {
            // Preprocesar imagen: Convertir a escala de grises y aumentar contraste
            $outputPath = preg_replace('/\.(jpg|png)$/', '', $imagePath);

            // Usar configuración óptima para CAPTCHA alfanumérico de una sola línea
            // NOTA: NO usar whitelist porque filtra caracteres incorrectamente
            $config = '--psm 7';

            $command = sprintf(
                '/usr/bin/tesseract %s %s %s 2>&1',
                escapeshellarg($imagePath),
                escapeshellarg($outputPath),
                $config
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                // Leer el resultado
                $resultFile = $outputPath . '.txt';

                if (file_exists($resultFile)) {
                    $text = file_get_contents($resultFile);
                    unlink($resultFile);

                    // Limpiar el resultado
                    $codigo = preg_replace('/[^A-Z0-9]/i', '', $text);
                    $codigo = trim($codigo);
                    $codigo = strtoupper($codigo);

                    \Log::info('CAPTCHA leído', [
                        'codigo' => $codigo,
                        'codigo_length' => strlen($codigo),
                        'raw_text' => trim($text)
                    ]);

                    // Si obtuvimos un código válido, retornarlo
                    // Los CAPTCHAs del SENIAT tienen 6-7 caracteres
                    if (strlen($codigo) >= 4 && strlen($codigo) <= 8) {
                        return $codigo;
                    }
                }
            }

            \Log::error('No se pudo leer el CAPTCHA', [
                'return_code' => $returnCode,
                'output' => $output
            ]);
            return '';

        } catch (\Exception $e) {
            \Log::error('Error leyendo CAPTCHA con OCR: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Debug: Descargar CAPTCHA y mostrar información
     */
    public function debugCaptcha(): array
    {
        try {
            // Descargar CAPTCHA original
            $tempPath = storage_path('app/temp/captcha_' . time() . '.jpg');
            $captchaContent = Http::timeout(10)->get($this->captchaUrl)->body();
            file_put_contents($tempPath, $captchaContent);

            // Convertir imagen original a base64
            $originalImageData = base64_encode($captchaContent);

            // Aplicar preprocesamiento
            $processedPath = $this->preprocesarImagen($tempPath);

            // Leer CAPTCHA procesado con OCR
            $captchaCode = $this->leerCaptchaConOCR($processedPath);

            // Convertir imagen procesada a base64
            $processedImageData = base64_encode(file_get_contents($processedPath));
            $imageType = pathinfo($processedPath, PATHINFO_EXTENSION);

            // Limpiar archivos temporales
            if (file_exists($processedPath)) {
                unlink($processedPath);
            }

            return [
                'success' => true,
                'ocr_result' => $captchaCode,
                'image_type' => $imageType,
                'original_image_base64' => $originalImageData,
                'processed_image_base64' => $processedImageData,
                'image_size' => strlen($captchaContent)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validar formato básico del RIF
     */
    private function validarFormatoRif(string $rif): bool
    {
        // Formato SIN guiones: J123456789, V123456789, E123456789, G123456789
        // Longitud total: 10 caracteres (1 letra + 9 dígitos)
        $pattern = '/^[JVEG]\d{9}$/i';

        return preg_match($pattern, $rif) === 1;
    }

    /**
     * Agregar guiones al RIF para el SENIAT
     * J123456789 -> J-12345678-9
     */
    private function agregarGuiones(string $rif): string
    {
        // Extraer letra y dígitos
        $letra = substr($rif, 0, 1);
        $digitos = substr($rif, 1);

        // Formatear con guiones: J-12345678-9
        return sprintf('%s-%s-%s',
            $letra,
            substr($digitos, 0, 8),
            substr($digitos, 8, 1)
        );
    }

    /**
     * Extraer el nombre de la respuesta HTML del SENIAT
     */
    private function extraerNombreDeRespuesta(string $html): ?string
    {
        // Patrón específico del SENIAT: buscar después de "<!-- VISUALIZAR RIF -->"
        if (preg_match('/<!--\s*VISUALIZAR RIF\s*-->/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1] + strlen($matches[0][0]);

            // Extraer la sección después del comentario
            $section = substr($html, $offset, 1000); // Leer siguientes 1000 caracteres

            // El formato es: <b><font face="Verdana" size="2">RIF&nbsp;NOMBRE</font>
            if (preg_match('/<b><font\s+face="Verdana"\s+size="2">([^<]+)<\/font>/i', $section, $fontMatch)) {
                $contenido = trim($fontMatch[1]);

                // Reemplazar &nbsp; con espacio
                $contenido = str_replace('&nbsp;', ' ', $contenido);

                // El formato es: RIF NOMBRE (ej: "V195071884 JOSEPH LUIGI MUENTES PICO")
                // Extraer solo el nombre (todo después del RIF)
                if (preg_match('/^[JVEG]\d+\s+(.+)$/', $contenido, $nombreMatch)) {
                    $nombre = trim($nombreMatch[1]);

                    \Log::info('Nombre extraído de SENIAT', [
                        'contenido_completo' => $contenido,
                        'nombre' => $nombre
                    ]);

                    if (strlen($nombre) > 2) {
                        return $nombre;
                    }
                }
            }

            // Patrón alternativo: buscar cualquier etiqueta <font> con tamaño 2
            if (preg_match('/<font\s+face="Verdana"\s+size="2">([^<]+)<\/font>/i', $section, $fontMatch)) {
                $contenido = trim($fontMatch[1]);
                $contenido = str_replace('&nbsp;', ' ', $contenido);

                // Intentar extraer el nombre
                if (preg_match('/^[JVEG]\d+\s+(.+)$/', $contenido, $nombreMatch)) {
                    $nombre = trim($nombreMatch[1]);
                    if (strlen($nombre) > 2) {
                        return $nombre;
                    }
                }
            }
        }

        // Si no se encuentra nada, devolver null
        return null;
    }

    /**
     * Limpiar y normalizar el nombre extraído
     */
    private function limpiarNombre(string $nombre): string
    {
        // Eliminar espacios extras
        $nombre = preg_replace('/\s+/', ' ', $nombre);

        // Eliminar caracteres especiales de HTML
        $nombre = html_entity_decode($nombre, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Eliminar tags HTML si existen
        $nombre = strip_tags($nombre);

        return trim($nombre);
    }

    /**
     * Validar RIF sin consultar al SENIAT (solo formato)
     */
    public function validarFormato(string $rif): array
    {
        $rif = strtoupper(trim($rif));

        if (!$this->validarFormatoRif($rif)) {
            return [
                'success' => true,
                'valid' => false,
                'message' => 'Formato de RIF inválido',
                'rif' => $rif
            ];
        }

        return [
            'success' => true,
            'valid' => true,
            'message' => 'Formato de RIF válido',
            'rif' => $rif
        ];
    }

    /**
     * Debug: Obtener respuesta cruda del SENIAT
     */
    public function debugResponse(string $rif): array
    {
        try {
            $rif = strtoupper(trim($rif));

            if (!$this->validarFormatoRif($rif)) {
                return [
                    'success' => false,
                    'message' => 'Formato de RIF inválido',
                    'rif' => $rif
                ];
            }

            // Enviar RIF original (sin guiones)
            $response = Http::timeout(10)
                ->asForm()
                ->post($this->baseUrl, [
                    'p_rif' => $rif
                ]);

            $html = $response->body();

            // Limpiar y codificar HTML para evitar errores de UTF-8
            $htmlClean = $this->cleanHtml($html);

            return [
                'success' => true,
                'rif_enviado' => $rif,
                'status_code' => $response->status(),
                'html_length' => strlen($html),
                'html_preview' => substr($htmlClean, 0, 1000),
                'html_full' => $htmlClean,
                'html_base64' => base64_encode($html) // Opción adicional sin codificación
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * Limpiar HTML para evitar errores de codificación UTF-8
     */
    private function cleanHtml(string $html): string
    {
        // Eliminar caracteres inválidos UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $html = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $html);

        // Reemplazar caracteres problemáticos (comillas tipográficas)
        $html = str_replace(
            ['"', '"', '', ''],
            ['"', '"', "'", "'"],
            $html
        );

        return $html;
    }

    /**
     * Obtener imagen del CAPTCHA del SENIAT en base64
     *
     * @return string|null Imagen en base64 o null si falla
     */
    public function obtenerCaptchaImagen(): ?string
    {
        try {
            $response = Http::timeout(10)->get($this->captchaUrl);

            if (!$response->successful()) {
                \Log::error('Error descargando CAPTCHA del SENIAT', [
                    'status' => $response->status()
                ]);
                return null;
            }

            $imageData = $response->body();
            $base64Image = base64_encode($imageData);

            \Log::info('CAPTCHA descargado exitosamente', [
                'size' => strlen($imageData)
            ]);

            return $base64Image;

        } catch (\Exception $e) {
            \Log::error('Error obteniendo imagen del CAPTCHA: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Consultar RIF con CAPTCHA ingresado manualmente por el usuario
     *
     * @param string $rif Formato: J123456789 o V123456789
     * @param string $captcha Código CAPTCHA ingresado por el usuario
     * @return array
     */
    public function consultarRifConCaptchaManual(string $rif, string $captcha): array
    {
        try {
            // Normalizar el RIF
            $rif = strtoupper(trim($rif));
            $captcha = strtoupper(trim($captcha));

            // Validar formato básico del RIF antes de consultar
            if (!$this->validarFormatoRif($rif)) {
                return [
                    'success' => false,
                    'valid' => false,
                    'message' => 'Formato de RIF inválido. Use: J123456789 o V123456789 (SIN guiones)',
                    'nombre' => null,
                    'rif' => $rif
                ];
            }

            // Validar que el CAPTCHA no esté vacío
            if (empty($captcha) || strlen($captcha) < 4) {
                return [
                    'success' => false,
                    'valid' => false,
                    'message' => 'El código CAPTCHA es inválido o está vacío',
                    'nombre' => null,
                    'rif' => $rif
                ];
            }

            // Verificar cache primero
            $cacheKey = 'seniat_rif_' . md5($rif);
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Enviar formulario al SENIAT
            $formData = [
                'p_rif' => $rif,
                'p_cedula' => '',
                'codigo' => $captcha
            ];

            \Log::info('Enviando formulario al SENIAT (CAPTCHA manual)', [
                'rif' => $rif,
                'captcha' => $captcha,
                'form_data' => $formData
            ]);

            $response = Http::timeout(15)
                ->asForm()
                ->post($this->baseUrl, $formData);

            $html = $response->body();

            // Convertir de WINDOWS-1252 a UTF-8
            $html = mb_convert_encoding($html, 'UTF-8', 'WINDOWS-1252');

            \Log::info('Respuesta recibida del SENIAT', [
                'rif' => $rif,
                'response_length' => strlen($html)
            ]);

            // Verificar si el SENIAT rechazó el CAPTCHA
            if (preg_match('/EL\s+c[oó]digo\s+no\s+coincide\s+con\s+la\s+imagen/ui', $html)) {
                \Log::info('CAPTCHA rechazado por el SENIAT', ['rif' => $rif]);
                return [
                    'success' => false,
                    'valid' => false,
                    'message' => 'El código CAPTCHA es incorrecto. Por favor, intente nuevamente.',
                    'nombre' => null,
                    'rif' => $rif
                ];
            }

            // Extraer nombre de la respuesta
            $nombre = $this->extraerNombreDeRespuesta($html);

            if ($nombre && $nombre !== '' && strlen($nombre) > 2 && $nombre !== '&nbsp;') {
                $result = [
                    'success' => true,
                    'valid' => true,
                    'message' => 'RIF encontrado',
                    'nombre' => $this->limpiarNombre($nombre),
                    'rif' => $rif
                ];

                // Guardar en cache por 7 días
                Cache::put($cacheKey, $result, 60 * 60 * 24 * 7);

                return $result;
            }

            // Si no hay nombre, el RIF no existe
            return [
                'success' => true,
                'valid' => false,
                'message' => 'RIF no encontrado en el SENIAT',
                'nombre' => null,
                'rif' => $rif
            ];

        } catch (\Exception $e) {
            \Log::error('Error consultando RIF con CAPTCHA manual: ' . $e->getMessage());
            return [
                'success' => false,
                'valid' => false,
                'message' => 'Error al consultar el RIF: ' . $e->getMessage(),
                'nombre' => null,
                'rif' => $rif
            ];
        }
    }
}
