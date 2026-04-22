# Checklist de produccion

Antes de publicar o actualizar Vendly en produccion:

- Verificar que `.env` tenga `APP_ENV=production`, `APP_DEBUG=false` y `APP_URL` con el dominio real HTTPS.
- Ejecutar migraciones con `php artisan migrate --force`.
- Confirmar que `php artisan storage:link` exista en el servidor.
- Compilar caches con `php artisan config:cache`, `php artisan route:cache` y `php artisan view:cache`.
- Ejecutar `php artisan test` antes de desplegar cambios.
- Revisar permisos de escritura en `storage` y `bootstrap/cache`.
- Confirmar que las imagenes publicas carguen desde `/storage`.
- Probar una tienda publica, un producto compartido por WhatsApp y un checkout real.
- Validar que usuarios vencidos no puedan vender ni mostrar su tienda publica.
- Confirmar que el certificado HTTPS este activo.
- Hacer backup de base de datos y archivos de `storage/app/public`.
