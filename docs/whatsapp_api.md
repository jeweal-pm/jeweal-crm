# WhatsApp Delivery API

## Public endpoint

`POST /api/whatsapp/messages`

```json
{
  "phone_number": "+66912345678",
  "message": "Your quotation is ready.",
  "reference_id": "QUOTE-1001"
}
```

- `phone_number` is required and must use international E.164 format.
- `message` is required and accepts up to 1,600 characters.
- `reference_id` is optional.
- A recipient number can only exist once. Delete its CRM record before reusing a test number.

## Responses

- `200`: Twilio accepted the message and the record is complete.
- `202`: Saved in Waiting because of the daily quota, incomplete configuration, or a provider failure.
- `403`: Source IP is globally blacklisted.
- `409`: A record for the recipient number already exists.
- `422`: Payload validation failed.
- `429`: The IP cooldown or module rate limit was reached.

## CRM pages

- `/whatsapp/messages`: Waiting, Complete and Failed message lists.
- `/whatsapp/config`: Encrypted Twilio API credentials, sender, daily quota and retry policy.
- `/security/ip-controls`: Global blacklist, per-module rate limits and IP decision log.

Twilio authentication requires the Account SID, API Key SID and API Key Secret. The WhatsApp sender must already be approved in the same Twilio account.

## Scheduler

The existing Laravel scheduler now runs `whatsapp:process-waiting` every minute. Production only needs one cron entry that executes:

```bash
php artisan schedule:run
```

## Deployment

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=CommunicationSecuritySeeder --force
php artisan optimize:clear
```
