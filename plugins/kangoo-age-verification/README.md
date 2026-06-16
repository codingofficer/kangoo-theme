# Kangoo Age Verification

Mandatory WooCommerce checkout verification using Stripe Identity government photo ID plus matching selfie.

The plugin also contains a dormant VerifyMyAge Stores & Custom API integration for post-checkout order checks.

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

## VerifyMyAge preparation

VerifyMyAge is configured separately under **WooCommerce > VerifyMyAge**.

Required credentials:

1. Sandbox API ID.
2. Sandbox API secret.
3. Production API ID.
4. Production API secret.

Webhook/callback URL:

`/wp-json/kangoo-age-verification/v1/webhook/verifymyage`

The integration remains off until **Submit paid/processing orders to VerifyMyAge** is enabled. In sandbox mode, automatic submission is admin-order-only by default. Orders can also be submitted or refreshed manually from the WooCommerce order actions dropdown.

When enabled, the plugin sends paid/processing/completed WooCommerce orders to VerifyMyAge with the order ID, billing customer data, purchased products, callback URL, and notification preferences. VerifyMyAge responses are stored as order meta and order notes. An `Approved` status records the existing Kangoo age-verification meta as 18+ verified with provider `VerifyMyAge`.

The optional **Move Pending/Failed/Expired verification orders to On hold** setting should stay disabled until fulfilment rules are agreed.
