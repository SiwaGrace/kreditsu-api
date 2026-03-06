# Authentication Strategy Decision

## Decision: Laravel Sanctum with httpOnly Cookie Auth

### Date: 2026-03-05

---

## Context

The project is a decoupled React + Laravel setup where React runs as a standalone SPA and Laravel serves as a pure JSON API. Authentication needs to be secure, maintainable, and appropriate for the current stage of the project.

Two approaches were considered:

**Option A — Sanctum Token Auth (access token + refresh token)**
Laravel generates a plain text token returned in the response body. React stores the access token in Redux (memory) and a refresh token is stored in an httpOnly cookie. On page refresh, a silent call to `/api/refresh` restores the access token from the refresh token cookie.

**Option B — Sanctum Cookie/Session Auth (httpOnly cookie only)**
Laravel issues a session cookie after login. The browser handles the cookie automatically on every request. React never sees or manages a token. Redux only stores the user object and `isAuthenticated` state.

---

## Decision

**Option B — Sanctum Cookie/Session Auth.**

---

## Reasons

- Simpler implementation with no token management on the frontend
- No risk of accidentally exposing tokens via localStorage
- The browser handles cookie attachment automatically on every request
- Redux `authSlice` stays clean — only stores user data and auth status, not tokens
- The dual token strategy (Option A) adds complexity that is not justified at the current stage
- Equally secure for a same-origin or controlled cross-origin setup with proper CORS configuration

---

## Consequences

- `withCredentials: true` must be set on the Axios instance for cookies to be sent cross-origin
- Laravel CORS must explicitly allow the React dev origin (`http://localhost:5173`) and production domain
- A `GET /sanctum/csrf-cookie` call must be made before every login attempt
- If the project later requires a mobile app or third party API consumers, token auth will need to be reconsidered as cookies are not ideal in those contexts

---

## What We Are Not Doing

- Not storing tokens in localStorage (vulnerable to XSS)
- Not implementing a refresh token rotation strategy (unnecessary complexity at this stage)
- Not using JWT manually (Sanctum handles this internally)
