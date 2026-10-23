<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

## API de reservas de canchas

La API usa tokens Bearer de Sanctum. Envía `Accept: application/json` y, para rutas protegidas, `Authorization: Bearer <token>`.

### Cuenta y sesión

- `POST /api/register`: crea una cuenta cliente. Requiere `username`, `name`, `email`, `password` y `password_confirmation`; acepta opcionalmente `gender` y `birthdate`. El rol siempre lo asigna el servidor.
- `POST /api/login`: requiere `username`, `password` y `device_name`; devuelve el token y el perfil.
- `GET /api/user` y `POST /api/logout`: requieren autenticación.

### Canchas

- `GET /api/canchas`: lista canchas activas. Usa `?date=YYYY-MM-DD` para incluir las reservas de ese día y mostrar horarios ocupados.
- `POST /api/canchas`, `PUT/PATCH /api/canchas/{id}` y `DELETE /api/canchas/{id}`: solo administrador. La creación requiere `name`, `price_per_hour`, `opens_at` y `closes_at` en formato `HH:mm`; `surface` es opcional.

### Reservas y factura

- `GET /api/reservations`: lista las reservas del cliente autenticado.
- `POST /api/reservations`: requiere `cancha_id`, `starts_at`, `ends_at` (fechas ISO 8601) y `payment_method` (`cash`, `card` o `transfer`). El servidor comprueba horario y disponibilidad, calcula el importe según la tarifa por hora y genera `invoice_number`. La respuesta contiene la factura/reserva con pago pendiente.

El método de pago se guarda como elección para la factura; esta API no procesa cobros con bancos o pasarelas. Para inicializar una base nueva: `php artisan migrate --seed`. El seeder crea el administrador `admin` con contraseña inicial `Hola123`; cámbiala antes de exponer el servicio.

## Ejecutar la aplicación

Desde PowerShell, inicia el backend y la base:

```powershell
cd C:\proyecto_jlinares_12a_4p\backend_canchas_jlinares
php artisan migrate --seed
php artisan serve
```

En otra terminal, inicia Flutter Web:

```powershell
cd C:\proyecto_jlinares_12a_4p\frontend_canchas_jlinares\frontend_canchas_jlinares
flutter pub get
flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

## Reservas, administradores y caja

- El cliente puede cancelar o reprogramar una reserva futura pendiente de pago. La reprogramacion solo se guarda si el horario esta dentro del horario de la cancha y no se cruza con otra reserva.
- `POST /api/admins` permite al administrador autenticado crear otra cuenta con rol admin.
- El panel de caja consume `GET /api/admin/cashier?date=YYYY-MM-DD`, donde muestra ingresos cobrados, gastos registrados, neto y movimientos del dia.
- `GET /api/admin/pending-reservations` lista facturas pendientes. `POST /api/admin/reservations/{id}/payment` confirma el cobro y lo registra una sola vez en la caja. `POST /api/admin/cashier/expenses` registra un gasto.
- Despues de actualizar el proyecto, ejecuta `php artisan migrate` para crear la tabla de movimientos de caja.

Los cobros con tarjeta o transferencia se registran como confirmados por el administrador; no se conectan a una pasarela bancaria.

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
