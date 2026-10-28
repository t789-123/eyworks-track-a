# eyworks Track A API

## Overview

A Laravel 12 / PHP API for multi-tenant room management and child check-ins. This is Track A only; no frontend or UI is required.

## Requirements and setup

- PHP 8.2 or later within the Laravel 12 supported range, Composer, and a configured MariaDB or MySQL server.
- Create a database, for example `eyworks_track_a`, and configure its connection in `.env`. In MariaDB or MySQL, one example is `CREATE DATABASE eyworks_track_a CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`. The checked-in `.env.example` uses MySQL on `127.0.0.1:3307`; adjust the host, port, database name, and credentials for your environment. Keep real credentials out of version control.

From the project directory:

```bash
composer install
cp .env.example .env
```

Edit `.env` with the local database settings, then run:

```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

The API is then available at `http://127.0.0.1:8000`. `php artisan migrate` applies pending migrations; it does not reset existing tables. Do not use database-reset commands on a database with data to preserve.

## Tenant isolation

Every API request must include `X-Tenant-ID` with an existing numeric tenant ID. Missing or malformed values return `400`; an unknown tenant returns `404`. The middleware resolves the tenant and attaches it to the request. Controllers verify that requested rooms, children, and check-ins belong to that tenant before reading or changing them. Check-in creation also verifies that the room and child share the tenant.

`X-Tenant-ID` selects a tenant scope; it is not authentication or proof that a caller is authorized to use that tenant. Authentication is not implemented in this Track A API.

## API

Send `X-Tenant-ID: <tenant_id>` on every request. For JSON requests, use `Content-Type: application/json` and `Accept: application/json`.

### Check a child into a room

`POST /api/rooms/{room}/check-ins`

Request body:

```json
{
  "child_id": 123
}
```

On success, returns `201 Created` with a message and `data` containing the check-in `id`, `tenant_id`, `room_id`, `child_id`, ISO-8601 `checked_in_at`, and `checked_out_at: null`.

Relevant errors:

- `400`: missing or invalid tenant header.
- `404`: unknown tenant or room not found for the tenant.
- `409`: child already has an active check-in.
- `422`: missing, malformed, or invalid child ID, including a child belonging to another tenant.

### Check a child out

`POST /api/check-ins/{id}/check-out`

No request body is required. On success, returns `200 OK` with a message and the updated check-in data, including ISO-8601 check-in and checkout timestamps.

Relevant errors: `400` for a missing or invalid tenant header, `404` when the check-in is missing or belongs to another tenant, and `409` if it has already been checked out.

### List current occupants of a room

`GET /api/rooms/{room}/occupants`

On success, returns `200 OK` with `data`, an array of active children in that tenant's room. Each item contains `id`, `tenant_id`, and `name`; an empty room returns `{"data": []}`. A missing or cross-tenant room returns `404` (and tenant-header errors use `400` or `404` as described above).

## Active check-in rule

A child cannot have two active check-ins at once. A second check-in returns `409 Conflict`; it does not automatically move the child to another room. This avoids silently changing a child's room assignment. Staff must check the child out first, then check them into the new room.

## Migrations

The project migrations create `tenants`, `rooms`, `children`, and `check_ins`, alongside Laravel's default tables. Rooms and children have tenant foreign keys and tenant-scoped name uniqueness. Check-ins reference a tenant, room, and child and have indexes for active-occupancy queries. Tenant ownership is also checked in the API controllers.

## Tests

Run the suite with:

```bash
php artisan test
```

The feature tests cover successful check-in and checkout, duplicate active check-in rejection, invalid and unknown tenant headers, cross-tenant access, active occupants, and empty rooms. A basic application response and unit example are also included. PHPUnit is configured to use an in-memory SQLite database; the API feature tests use Laravel's `RefreshDatabase` trait.

## With more time

- Integrate tenant resolution with the deployment's authentication and authorization system.
- Add database-level constraints that enforce tenant consistency across check-in, room, and child references.
- Expand coverage for concurrent check-in and checkout requests and document the API with an OpenAPI specification.

## Deliberately out of scope

- Frontend or UI, as this is Track A's API-only deliverable.
- Authentication implementation; in a deployed system, tenant access should be bound to the surrounding platform's authenticated identity rather than trusting the header alone.

## AI tools

GitHub Copilot assisted with implementation, tests, and documentation. Its suggestions were reviewed and adjusted where appropriate; not every suggestion was accepted.
