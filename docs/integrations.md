# Integrations & Homelab Architecture

The **Ternis API system** unifies multiple services, external partner APIs, and self-hosted homelab infrastructure into a single unified interface.

---

## Architecture Overview

| Integration | Purpose | Service Class | Base URI / Provider |
| :--- | :--- | :--- | :--- |
| **DomainBox** | Domain management & portfolio stats | `App\API\DomainBox` | `https://dnbx.de/api/` |
| **GitHub** | Activity feeds, repos & commit tracking | `App\API\GitHub` | `https://api.github.com/` |
| **StoryGrab** | Instagram media archiving & stories | `App\API\StoryGrab` | `https://storygrab.net/api/v1/partner/` |
| **TwinsOnIceLink** | Short link generation | `App\API\TwinsOnIceLink` | `https://api.twinsonice.link/v1/` |
| **Cloudflare Turnstile** | CAPTCHA token verification | `App\API\Turnstile` | `https://challenges.cloudflare.com/turnstile/v0/` |
| **HackClub CDN** | Asset storage (v4 API) | `App\API\HackClubCDN` | `https://cdn.hackclub.com/` |

---

## Homelab Infrastructure Stack

The API system exposes hardware details and software service configurations running on the homelab network:

- **WireGuard**: Secure VPN network mesh connecting internal servers.
- **Pi-Hole**: DNS filtering and ad blocking.
- **Immich**: Self-hosted photo and video backup server.
- **NextCloud**: Cloud file storage and productivity suite.
- **Gitea**: Self-hosted Git code repository server.
- **Docker**: Container orchestration across nodes.
- **n8n**: Workflow automation.
- **Jellyfin**: Media streaming platform.

---

## Caching Strategy

To maintain sub-10ms response times and respect third-party rate limits:
- **DomainBox Active Domains**: Cached for 600s (10 minutes).
- **StoryGrab Stories**: Cached for 300s (5 minutes).
- **GitHub Commit Activity**: Cached for 300s (5 minutes).
- **Rendered Documentation Pages**: Cached until a new **git commit** occurs (`git rev-parse HEAD`), triggering instant automatic cache invalidation.
