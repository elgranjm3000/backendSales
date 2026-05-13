# Configuración de 2Captcha para Consulta SENIAT

## ¿Qué es 2Captcha?

**2Captcha** es un servicio de resolución de CAPTCHAs con humanos que ofrece:
- Tasa de éxito del **95-99%**
- Tiempo de resolución: **10-20 segundos** en promedio
- Costo: **$0.50 - $1.00 USD por 1000 CAPTCHAs**
- Soporte para todo tipo de CAPTCHAs

## Pasos para configurar

### 1. Crear cuenta en 2Captcha

1. Ve a: https://2captcha.com/
2. Regístrate con tu email
3. Confirma tu cuenta
4. Deposita fondos (mínimo $5 USD)

Métodos de pago aceptados:
- Tarjetas de crédito/débito
- PayPal
- Bitcoin
- Criptomonedas (USDT, Litecoin, etc.)

### 2. Obtener tu API Key

1. Inicia sesión en 2Captcha
2. Ve a la sección **"Perfil"** o **"Account"**
3. Copia tu **API Key** (clave de 32 caracteres)

### 3. Configurar en el proyecto

Edita el archivo `.env`:

```bash
TWOCAPTCHA_API_KEY=tu_api_key_de_32_caracteres_aqui
```

Ejemplo:
```bash
TWOCAPTCHA_API_KEY=1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p
```

### 4. Probar la configuración

Consulta tu saldo:

```bash
curl "http://localhost/api/seniat/captcha-saldo" \
  -H "Host: sales-apiWEB.local"
```

Respuesta esperada:
```json
{
    "success": true,
    "saldo": 5.00,
    "moneda": "USD",
    "servicio": "2Captcha",
    "message": "Saldo actual: $5.00 USD"
}
```

## Uso de los endpoints

### Consultar RIF (usará 2Captcha automáticamente)

```bash
curl "http://localhost/api/seniat/consultar?rif=V195071884" \
  -H "Host: sales-apiWEB.local"
```

### Consultar con reintentos automáticos

```bash
curl "http://localhost/api/seniat/consultar-reintentos?rif=V195071884&max_reintentos=3" \
  -H "Host: sales-apiWEB.local"
```

### Consultar saldo de 2Captcha

```bash
curl "http://localhost/api/seniat/captcha-saldo" \
  -H "Host: sales-apiWEB.local"
```

## Cómo funciona

1. **Primer intento**: Usa 2Captcha (si hay API key configurada)
   - Descarga el CAPTCHA del SENIAT
   - Lo envía a 2Captcha
   - Espera la solución (10-20 segundos)
   - Envía el formulario al SENIAT

2. **Fallback**: Si 2Captcha falla o no hay API key
   - Usa Tesseract OCR local
   - Tasa de éxito más baja (~10-20%)

## Costos estimados

| Consultas | Costo aproximado |
|-----------|------------------|
| 100       | $0.05 - $0.10    |
| 1,000     | $0.50 - $1.00    |
| 10,000    | $5.00 - $10.00   |

**Nota**: Cada consulta de RIF usa 1 CAPTCHA = $0.001 aproximadamente

## Monitoreo

### Ver logs de uso de 2Captcha

```bash
tail -f /home/muentes/lamp/www/sales-apiWEB/storage/logs/laravel.log | grep "2Captcha"
```

### Ver saldo en tiempo real

```bash
curl "http://localhost/api/seniat/captcha-saldo" -H "Host: sales-apiWEB.local"
```

## Troubleshooting

### Error: "2Captcha API key no configurada"
**Solución**: Agrega tu API key en el archivo `.env`

### Error: "No se pudo obtener el saldo"
**Causas posibles**:
- API key incorrecta
- Sin conexión a internet
- Servidor de 2Captcha caído

**Solución**: Verifica tu API key y prueba en https://2captcha.com/

### CAPTCHA demora mucho (más de 30 segundos)
**Causa**: 2Captcha puede estar sobrecargado
**Solución**: El sistema hará 20 intentos con 3 segundos de espera entre cada uno

### El CAPTCHA resuelto es incorrecto
**Causa**: Rara vez, el humano puede equivocarse
**Solución**: El sistema reintentará automáticamente

## Ventajas de usar 2Captcha

✅ **Alta tasa de éxito**: 95-99% vs 10-20% con Tesseract
✅ **Confiable**: Humanos reales resuelven los CAPTCHAs
✅ **Económico**: Menos de $0.001 por consulta
✅ **Automático**: No necesitas intervenir manualmente
✅ **Con fallback**: Si falla, usa Tesseract local

## Alternativas a 2Captcha

Si prefieres otros servicios, el código se puede adaptar fácilmente para:

- **Anti-Captcha**: $0.50 por 1000 CAPTCHAs
- **DeathByCaptcha**: $1.99 por 1000 CAPTCHAs
- **CapSolver**: $0.80 por 1000 CAPTCHAs

Todos tienen APIs similares.

## Ejemplo de implementación en frontend

```javascript
async function consultarRIF(rif) {
  try {
    const response = await fetch(
      `https://tu-api.com/api/seniat/consultar-reintentos?rif=${rif}&max_reintentos=3`,
      {
        headers: {
          'Authorization': `Bearer ${tuToken}`,
          'Accept': 'application/json'
        }
      }
    );

    const resultado = await response.json();

    if (resultado.success && resultado.valid) {
      console.log('Nombre:', resultado.nombre);
      return resultado;
    } else if (resultado.success && !resultado.valid) {
      console.log('RIF no encontrado');
      return resultado;
    } else {
      console.error('Error:', resultado.message);
      // Reintentar después de unos segundos
      setTimeout(() => consultarRIF(rif), 5000);
    }
  } catch (error) {
    console.error('Error de red:', error);
  }
}

// Uso
consultarRIF('V195071884');
```

## Soporte

Si tienes problemas:
- Documentación 2Captcha: https://2captcha.com/2captcha-api
- Soporte: support@2captcha.com
