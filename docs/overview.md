# Ternis Developer API Overview

Welcome to the **Ternis Developer API** documentation. The Ternis API system provides high-performance endpoints for inspecting system metrics, homelab infrastructure, DomainBox domains, GitHub activity feeds, and link shortening capabilities.

> [!IMPORTANT]
> **Domain Routing Rule**: When accessing the API via **`api.fabian.ternis.dev`**, the `/api` prefix is **not required**. Endpoints should be called directly under `/v1/...` (e.g. `https://api.fabian.ternis.dev/v1/health`).
> In development environments without the `api.` subdomain, endpoints are accessed under the `/api` path prefix (e.g. `http://localhost/api/v1/health`).

---

## Key Features

- **Direct Subdomain Routing**: When on `api.fabian.ternis.dev`, `/api` is omitted and clean `/v1/...` routes are preferred.
- **Dual Environment Support**: Seamless path resolution across production domains and local dev paths.
- **Commit-Aware Caching**: Rendered documentation and cached responses automatically synchronize with your latest git commit.
- **RESTful standard**: JSON payloads, structured error codes, standard HTTP status codes, and cross-origin (CORS) header support.
- **Interactive Documentation**: Test live endpoints directly from this interactive documentation console.

---

## Preferred Base URLs

| Environment | Host Header / Domain | Preferred Base URL | Example Endpoint |
| :--- | :--- | :--- | :--- |
| **Production (Domain)** | `api.fabian.ternis.dev` | `https://api.fabian.ternis.dev` *(no `/api` required)* | `https://api.fabian.ternis.dev/v1/health` |
| **Development (Local)** | `localhost` / dev server | `http://localhost/api` | `http://localhost/api/v1/health` |

---

## Response Format

All API endpoints return responses encapsulated in a uniform JSON envelope:

```json
{
  "success": true,
  "status": 200,
  "data": { ... },
  "meta": {
    "version": "1.0.0",
    "commit": "2272d2e",
    "timestamp": "2026-08-01T21:28:00+02:00"
  }
}
```

If an error occurs, the standard envelope structure is maintained:

```json
{
  "success": false,
  "status": 404,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "The requested endpoint or resource was not found."
  },
  "meta": {
    "version": "1.0.0",
    "commit": "2272d2e",
    "timestamp": "2026-08-01T21:28:00+02:00"
  }
}
```
