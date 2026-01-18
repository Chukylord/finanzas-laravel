# Finanzas - Laravel

App de finanzas personales multiusuario:
- Ingresos / Egresos con filtro por mes y totales
- Categorías (ingreso/egreso) protegidas si están en uso
- Recurrentes (Netflix, alquiler, etc.) con generación de egreso
- Deudas / cuotas con botón de pagar cuota (genera egreso y avanza vencimiento)
- Dashboard mensual con cards, ranking por categorías y vencimientos próximos
- UI moderna con Tailwind + Breeze

## Instalación
```bash
git clone https://github.com/TU_USUARIO/TU_REPO.git
cd finanzas
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve


### Tecnologías

Laravel 12

Breeze

TailwindCSS

MySQL