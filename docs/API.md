# API v1

Base path: `/api/v1`. Implemented endpoints use Laravel Sanctum bearer tokens, the authenticated user's business/assigned branch, module checks and a 60-request/minute route throttle. Use HTTPS outside local development.

## Tokens

Settings → API tokens lets an authorized account issue a current-user token with selected abilities and a 7, 30 or 90 day expiry after confirming its password. Copy the secret when it is displayed; it is not recoverable later. The same page lists and revokes the current user's tokens. It does not issue tokens for another user's identity.

A token's ability is required in addition to the applicable module and authorization checks. A token does not select a business. Its user's business and assigned branch determine scope. Keep tokens out of URLs and source control.

```bash
export MAILORA_URL='http://127.0.0.1:8000'
# Set MAILORA_TOKEN to the token issued in your own account.
curl --fail-with-body "$MAILORA_URL/api/v1/contacts" \
  -H "Authorization: Bearer $MAILORA_TOKEN" \
  -H 'Accept: application/json'
```

## Endpoints

| Method | Path | Ability | Modules |
|---|---|---|---|
| GET | `/contacts` | `contacts:read` | `api`, `contacts` |
| POST | `/contacts` | `contacts:write` | `api`, `contacts` |
| POST | `/events` | `events:write` | `api`, `events` |

### List contacts

`GET /api/v1/contacts` returns Laravel cursor pagination with up to 100 rows, ordered by internal ID. Use the returned `next_page_url` or cursor to request the next page. Each row exposes `public_id`, `email`, `first_name`, `last_name`, `status` and `score`. The endpoint is not an arbitrary filtering/query API.

### Create a pending contact

```bash
curl --fail-with-body "$MAILORA_URL/api/v1/contacts" \
  -H "Authorization: Bearer $MAILORA_TOKEN" \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  --data '{"email":"reader@example.test","first_name":"Ayesha","last_name":"Rahman"}'
```

Required: a valid email up to 254 characters and a first name up to 100 characters. Last name is optional, up to 100 characters. Email is normalized to lowercase. The user must also be allowed to create contacts.

A successful response is HTTP 201 with `public_id`, email, first name and status `pending`. It may enroll a matching `contact.created` automation. Creating a contact through the API does not establish marketing consent and cannot immediately make it eligible for email delivery. An existing business email returns 409.

### Submit a supported automation trigger

```bash
curl --fail-with-body "$MAILORA_URL/api/v1/events" \
  -H "Authorization: Bearer $MAILORA_TOKEN" \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  --data '{"contact_id":"CONTACT_PUBLIC_ID","event":"manual"}'
```

`contact_id` is the contact's public ID, not its database integer ID. Supported events are `manual`, `contact.created` and `form.submitted`. The contact must be in scope. A valid request returns HTTP 202 and `{"accepted":true}`. Runs are advanced by the scheduler. Enrollment is unique per flow/contact/version, so repeated delivery of the same trigger does not create another run for that version.

This endpoint is a small trigger interface. It does not accept arbitrary event payloads, persist a general event stream, track browser visits, or implement an SDK.

## Error behavior and limits

| Status | Meaning |
|---|---|
| 401 | Missing/invalid/expired bearer token |
| 403 | Missing ability or permission |
| 404 | Disabled module or unavailable/out-of-scope record |
| 409 | Duplicate email on contact create |
| 422 | Request validation failed |
| 429 | Request throttle exceeded |

Use `Accept: application/json` to receive JSON errors. Bearer-token calls do not require a browser CSRF token. Endpoints for sending transactional mail, campaigns, lists, templates, reports, webhooks and commerce synchronization are not implemented. There is no generated OpenAPI schema in this release. Never treat the full product catalog as the API surface.
