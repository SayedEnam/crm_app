# ZomZom Travels & High Think HR CRM

A full-stack client and service CRM with a Laravel REST API and a separate React SPA. It covers leads and clients, visa and student admission cases, certificate equivalency, document attestation, air tickets, HR consultancy, private application files, follow-ups, payments, reports, and user access.

## Project layout

- backend/ — Laravel 13, PHP 8.4+, Sanctum, MySQL/MariaDB migrations, seeders, factories, policies, Form Requests, API Resources, and versioned JSON routes.
- frontend/ — React 19, strict TypeScript, Vite, React Router, Tailwind CSS, shadcn-style UI components, Lucide, TanStack Query, React Hook Form, Zod, and Recharts.
- docker-compose.yml — MySQL 8.4, PHP-FPM API, Nginx API gateway, and static SPA containers.

## Local development

### Backend

1. Use PHP 8.4+, Composer, and MySQL 8+ or supported MariaDB. The included container uses PHP 8.4.
2. In backend/, copy .env.example to .env. Set APP_URL, APP_FRONTEND_URL, MySQL credentials, mail delivery, and SANCTUM_STATEFUL_DOMAINS. For local Vite use localhost:5173 in the Sanctum domains and http://localhost:5173 as the frontend/CORS origin.
3. Run composer install, php artisan key:generate, php artisan migrate --seed, then php artisan crm:bootstrap-admin. The bootstrap command asks for a one-time Super Admin name, email, and password; no default account is seeded.
4. Configure SMTP before using password resets or inviting staff. In production set APP_DEBUG=false, SESSION_SECURE_COOKIE=true, and HTTPS-only trusted hosts.

### Frontend

1. In frontend/, copy .env.example to .env.
2. For a separate local backend set VITE_API_URL=http://localhost:8000/api/v1 and VITE_BACKEND_URL=http://localhost:8000. For a same-origin reverse proxy, leave VITE_API_URL empty so the SPA calls /api/v1.
3. Run npm install and npm run dev.

The SPA first requests Sanctum’s /sanctum/csrf-cookie endpoint before login. Axios sends the XSRF header and session cookies with credentialed requests. The app does not put sessions, tokens, or passwords in localStorage.

## API

All application APIs are under /api/v1. Login, password reset, authenticated profile, dashboard, reports, and CRUD routes return JSON. Laravel validation errors use HTTP 422 responses. Protected routes require auth:sanctum plus an active account.

Core collections:

- /clients — clients and leads (status=lead)
- /applications — a client’s work against a service catalog
- /documents — private file upload, review status, and authorized download
- /follow-ups — owner-scoped reminders and callbacks
- /payments — outstanding, collected, refunded, and void payments
- /services — the six seeded service categories
- /users and /roles — activation and role assignment for authorized admins
- /dashboard and /reports — date-filtered, permission-scoped aggregates; report export is CSV

Use GET /sanctum/csrf-cookie before POST /api/v1/auth/login; follow with GET /api/v1/user to load the current session user. For split subdomains, set the cookie domain to the shared parent domain, list the frontend host in SANCTUM_STATEFUL_DOMAINS, allow the exact origin in CORS_ALLOWED_ORIGINS, use HTTPS, and set VITE_BACKEND_URL to the API origin.

## Roles and record visibility

The role seeder creates Super Admin, Admin, Manager, Staff, Accountant, and Viewer with backend permission mappings. Super Admin owns role assignments; Admin can manage users but cannot grant roles. Managers see records assigned to themselves and their directly assigned staff. Other non-admin users see records assigned to themselves. Server policies and scoped queries enforce access even when a client calls an endpoint directly.

## Container deployment

1. Copy the root .env.example to .env, replace database passwords, and set the public VITE_API_URL and VITE_BACKEND_URL build values. Set matching API and frontend origins in backend/.env.
2. Copy backend/.env.example to backend/.env, set a generated APP_KEY, database credentials, mail, frontend URL, Sanctum stateful domains, and cookie settings. In production use HTTPS and set SESSION_SECURE_COOKIE=true.
3. Build and start with docker compose up -d --build.
4. Run database setup with docker compose exec backend php artisan migrate --seed --force, then provision the first Super Admin with docker compose exec backend php artisan crm:bootstrap-admin.
5. The example exposes the SPA at port 8080 and the API at port 8081. Put TLS and host routing in front of those services for production. Persist mysql_data and backend_storage, back them up, and use a shared session/cache backend if scaling horizontally.

The Docker build installs PHP production dependencies and builds the React bundle. It does not run migrations or create credentials during container startup.
