# Ternis Developer API Overview

Welcome to the **Ternis Developer API** documentation. The Ternis API system provides high-performance endpoints for inspecting system metrics, homelab infrastructure, DomainBox domains, GitHub activity feeds, and link shortening capabilities.

> [!NOTE]
> In production environments, the API system is hosted on **`api.fabian.ternis.dev`**. In development environments, endpoints are available under the **/api** routing path (e.g. `/api/v1/...`).

---

## Key Features

- **Production & Dev Environment Support**: Dual domain and subpath endpoint dispatching.
- **Commit-Aware Caching**: Rendered documentation and cached responses automatically synchronize with your latest git commit.
- **RESTful standard**: JSON payloads, structured error codes, standard HTTP status codes, and cross-origin (CORS) header support.
- **Interactive Documentation**: Test live endpoints directly from this interactive documentation console!

---

## Base URLs

| Environment | Base URL |
| :--- | :--- |
| **Production Domain** | `https://api.fabian.ternis.dev` |
| **Development Path** | `http://localhost/api` (or relative `/api`) |

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
    "commit": "054e1de",
    "timestamp": "2026-08-01T21:20:00+02:00"
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
    "commit": "054e1de",
    "timestamp": "2026-08-01T21:20:00+02:00"
  }
}
```
