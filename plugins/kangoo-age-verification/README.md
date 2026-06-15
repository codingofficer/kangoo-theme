# Kangoo Age Verification

Mandatory WooCommerce checkout verification using Stripe Identity government photo ID plus matching selfie.

## Activation

1. Create a separate Stripe Identity account or enable Identity in a direct Stripe account.
2. Create a restricted key with Identity Verification Sessions read/write and permission to access sensitive verification results.
3. Add the webhook URL shown under WooCommerce > Age Verification and subscribe to Identity verification session events.
4. Configure test keys, keep **administrators only** enabled, activate enforcement, and complete the test flow.
5. Configure live keys and switch to Live only after Stripe confirms the implementation.

WooPayments Connect credentials cannot be reused because they are controlled by WooPayments and are not exposed to this site.

## Data handling

WordPress stores only an opaque checkout token hash, provider session ID, pass/fail state, timestamp and linked order ID. It does not store document images, selfies, document numbers, addresses or extracted date of birth. Verified Stripe sessions are scheduled for redaction after 24 hours.

## Rollback

Deactivate this plugin. The legacy theme DOB gate remains unchanged and resumes automatically because the plugin removes its hooks only while enforcement is active.
