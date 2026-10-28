# Signed SMTP feedback

Enable feedback and open Delivery → Feedback Connections as owner/admin. Create a connection and copy its URL/secret into your MTA/gateway adapter. This accepts Mailora format, not raw vendor payloads.

POST JSON with event_id (stable unique gateway identifier), message_id (Mailora message ULID), and type (delivered, hard_bounce, soft_bounce, complaint). Optional occurred_at records event time.

Set X-Mailora-Timestamp to current Unix time. Set X-Mailora-Signature to lowercase hexadecimal HMAC-SHA256(secret, timestamp + "." + exact_raw_JSON_body). Do not reformat after signing. Clock window: five minutes. Maximum payload: 16 KiB. Use HTTPS in production.

202 means accepted for queued processing. Repeated identical event IDs do not repeat counters. Reusing an ID for a different message/type returns 409. Hard bounces/complaints suppress the original snapshotted recipient, even if their contact email was later edited. Signed receipts can reconcile uncertain SMTP attempts without resending.

Inactive connections and disabled modules reject new receipts. Processing pauses when feedback is disabled. Requeue after re-enabling using: php artisan feedback:resume BUSINESS_ID.

Secrets are encrypted with APP_KEY. Rotate in the connection UI and promptly update your gateway. Raw payloads and secrets are not added to audit metadata. Protect/back up APP_KEY securely.
