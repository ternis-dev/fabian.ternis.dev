# Authentication & Security

Public endpoints on the **Ternis API** (such as system status, domain listings, GitHub commit feeds, and API metadata) are accessible without authentication headers.

> [!TIP]
> Public GET endpoints support standard `Accept: application/json` headers and include CORS support for client-side JavaScript applications. On **`api.fabian.ternis.dev`**, requests do not require `/api` in the path.

---

## Direct Domain Routing Example

When calling from `api.fabian.ternis.dev`, use direct `/v1/...` routes:

```bash
curl -X POST https://api.fabian.ternis.dev/v1/shorten \
  -H "Content-Type: application/json" \
  -d '{"url": "https://fabian.ternis.dev"}'
```

---

## Error Handling

| HTTP Code | Exception | Description |
| :--- | :--- | :--- |
| `200 OK` | - | Successful request execution. |
| `400 Bad Request` | `INVALID_INPUT` | Missing or invalid parameters in request body. |
| `404 Not Found` | `RESOURCE_NOT_FOUND` | The specified endpoint or resource does not exist. |
| `405 Method Not Allowed` | `METHOD_NOT_ALLOWED` | HTTP method not permitted for this endpoint. |
| `500 Internal Error` | `INTERNAL_SERVER_ERROR` | An uncaught server-side exception occurred. |
