# Implementación SENIAT - Estado Actual

## ✅ Sistema FUNCIONANDO (con Tesseract OCR)

El endpoint de consulta de RIF está implementado y funcionando actualmente con **Tesseract OCR**.

### Endpoints disponibles:

```bash
# Consultar RIF (usa Tesseract OCR)
GET /api/seniat/consultar?rif=V195071884

# Consultar con reintentos automáticos (mejor tasa de éxito)
GET /api/seniat/consultar-reintentos?rif=V195071884&max_reintentos=3

# Validar formato de RIF
GET /api/seniat/validar-formato?rif=V195071884

# Debug: ver respuesta del SENIAT
GET /api/seniat/debug?rif=V195071884

# Debug: ver CAPTCHA actual
GET /api/seniat/debug-captcha
```

### Tasa de éxito actual:

**Con Tesseract OCR (actual):**
- Tasa de éxito: **~10-20%**
- Los CAPTCHAs del SENIAT son difíciles de leer automáticamente
- Tiempo de respuesta: 2-3 segundos

**Con 2Captcha (recomendado):**
- Tasa de éxito: **95-99%**
- Tiempo de respuesta: 15-25 segundos
- Costo: $0.001 por consulta

## Archivos creados:

1. **`/app/Services/SeniatService.php`** - Servicio principal para consulta SENIAT
2. **`/app/Services/CaptchaSolverService.php`** - Servicio para 2Captcha (listo para usar)
3. **`/app/Http/Controllers/Api/SeniatController.php`** - Controller con los endpoints
4. **`/routes/api.php`** - Rutas configuradas
5. **`/SENIAT_2CAPTCHA_SETUP.md`** - Guía completa de configuración de 2Captcha
6. **`/SENIAT_RESUMEN.md`** - Este archivo

## Cómo mejorar a 2Captcha (OPCIONAL)

Si quieres mejorar la tasa de éxito del 95-99%, sigue estos pasos:

### Paso 1: Crear cuenta en 2Captcha

1. Ve a: https://2captcha.com/
2. Regístrate y deposita fondos (mínimo $5 USD)
3. Copia tu API Key de 32 caracteres

### Paso 2: Configurar en el proyecto

Agrega tu API key en `.env`:

```bash
TWOCAPTCHA_API_KEY=tu_api_key_aqui
```

### Paso 3: Modificar SeniatService

En `/app/Services/SeniatService.php`, reemplaza el método `consultarRif`:

```php
public function consultarRif(string $rif): array
{
    try {
        $rif = strtoupper(trim($rif));

        if (!$this->validarFormatoRif($rif)) {
            return [
                'success' => false,
                'valid' => false,
                'message' => 'Formato de RIF inválido. Use: J123456789 o V123456789',
                'nombre' => null,
                'rif' => $rif
            ];
        }

        $cacheKey = 'seniat_rif_' . md5($rif);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Intentar resolver CAPTCHA con 2Captcha
        $captchaSolver = new \App\Services\CaptchaSolverService();
        $captchaCode = $captchaSolver->solveCaptcha($this->captchaUrl);

        // Si 2Captcha falló, usar Tesseract como fallback
        if (!$captchaCode) {
            $captchaPath = $this->descargarCaptcha();
            $captchaCode = $this->leerCaptchaConOCR($captchaPath);
            if (file_exists($captchaPath)) {
                unlink($captchaPath);
            }
        }

        if (!$captchaCode || strlen($captchaCode) < 4) {
            return [
                'success' => false,
                'valid' => false,
                'message' => 'No se pudo leer el CAPTCHA',
                'nombre' => null,
                'rif' => $rif
            ];
        }

        // Enviar formulario al SENIAT
        $response = Http::timeout(15)
            ->asForm()
            ->post($this->baseUrl, [
                'p_rif' => $rif,
                'codigo' => $captchaCode
            ]);

        $html = $response->body();
        $nombre = $this->extraerNombreDeRespuesta($html);

        if ($nombre && strlen($nombre) > 2) {
            $result = [
                'success' => true,
                'valid' => true,
                'message' => 'RIF encontrado',
                'nombre' => $this->limpiarNombre($nombre),
                'rif' => $rif
            ];
            Cache::put($cacheKey, $result, now()->addDays(7));
            return $result;
        }

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
        \Log::error('Error consultando RIF: ' . $e->getMessage());
        return [
            'success' => false,
            'valid' => false,
            'message' => 'Error al consultar el SENIAT',
            'nombre' => null,
            'rif' => $rif
        ];
    }
}
```

### Paso 4: Probar

```bash
# Verificar saldo
curl "http://localhost/api/seniat/captcha-saldo" -H "Host: sales-apiWEB.local"

# Consultar RIF (usará 2Captcha automáticamente)
curl "http://localhost/api/seniat/consultar?rif=V195071884" -H "Host: sales-apiWEB.local"
```

## Recomendaciones de uso:

### Para producción (usar 2Captcha):

- Costo: ~$0.001 por consulta
- Para 1000 consultas: $1.00 USD
- Tasa de éxito: 95-99%

### Para desarrollo/testing (usar Tesseract):

- Costo: Gratis
- Tasa de éxito: 10-20%
- Reintentar varias veces si falla

## Monitoreo:

Ver logs en tiempo real:

```bash
tail -f /home/muentes/lamp/www/sales-apiWEB/storage/logs/laravel.log
```

## Soporte:

Para más información sobre 2Captcha:
- Documentación: https://2captcha.com/2captcha-api
- Soporte: support@2captcha.com

## Ejemplo de respuesta exitosa:

```json
{
    "success": true,
    "valid": true,
    "message": "RIF encontrado",
    "nombre": "JOSEPH LUIGI MUENTES PICO",
    "rif": "V195071884"
}
```

## Ejemplo de RIF no encontrado:

```json
{
    "success": true,
    "valid": false,
    "message": "RIF no encontrado en el SENIAT",
    "nombre": null,
    "rif": "V195071884"
}
```

## Ejemplo de error de CAPTCHA:

```json
{
    "success": false,
    "valid": false,
    "message": "No se pudo leer el CAPTCHA. Intente nuevamente.",
    "nombre": null,
    "rif": "V195071884"
}
```
