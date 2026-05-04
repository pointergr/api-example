# Pointer API Documentation

This document describes the Pointer legacy XML API (v1) endpoints and their usage.

## Authentication

Every request requires a **checksum** calculated as:

```
md5(username + password + action + key)
```

Where:
- `username` — your account username
- `password` — your account password (plaintext)
- `action` — the XML action tag name (e.g. `login`, `domain`, `product`)
- `key` — the API key returned by `login` (empty string for the login request itself)

The checksum is included in every request as `<chksum>`.

Authenticated endpoints also require the `<key>` element obtained from a successful `login` call.

---

## Request / Response Format

All requests and responses use XML over HTTP POST.

**Request content-type:** `text/xml` or `application/xml`

**Response envelope:**

```xml
<pointer version="1.6.3.2">
    <action>...</action>
    <code>200</code>
    <message>Success</message>
</pointer>
```

### Response Codes

| Code | Meaning |
|------|---------|
| 200  | Success |
| 101  | No access to Pointer API — account not authorized for API access |
| 102  | Wrong credentials — invalid username or password |
| 103  | Invalid key — session key expired or tampered with |
| 104  | Too many connections — connection limit exceeded; retry after a delay |
| 105  | Client does not exist |
| 106  | Invalid request — malformed XML or missing required elements |
| 301  | Invalid arguments — valid request but a parameter value is out of range or of the wrong type |
| 302  | Product does not exist |
| 303  | Not enough credit — account balance too low to complete the operation |
| 304  | Domain cannot be locked — TLD does not support registry lock |
| 305  | Domain cannot be ID shielded — TLD or registrar does not support WHOIS privacy |
| 306  | Product cannot be upgraded/downgraded to the requested plan |
| 307  | Nameserver already exists |
| 308  | Unable to create nameserver — glue record creation failed at the registry |
| 309  | Cannot modify contact — contact is linked to an active domain and the registry rejects the update |
| 501  | API error — unexpected server-side error; contact support with the request details |

---

## Session Management

### Login

Authenticates with username and password, returns an API key for subsequent requests.

The `action` value for the checksum is `login`. The `key` segment of the checksum is an empty string.

**Request:**
```xml
<pointer>
    <login>
        <username>user</username>
        <password>pass</password>
    </login>
    <chksum>md5hash</chksum>
</pointer>
```

**Response:**
```xml
<pointer version="1.6.3.2">
    <login>
        <key>a1b2c3d4e5f6...</key>
    </login>
    <code>200</code>
    <message>Success</message>
</pointer>
```

**Errors:** 102 (wrong credentials), 101 (no API access)

---

### Logout

Invalidates the current API key. Always call this when the session is no longer needed.

**Request:**
```xml
<pointer>
    <logout/>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103 (invalid key)

---

## Domain Operations

### Create Domain

Registers a new domain name. Deducts the registration fee from the account balance.

| Field | Required | Description |
|-------|----------|-------------|
| `domain` | Yes | Domain name without TLD (e.g. `example`) |
| `duration` | Yes | Registration period in years |
| `ns1` | Yes | Primary nameserver hostname |
| `ns2` | Yes | Secondary nameserver hostname |
| `registrant` | Yes | Contact code of the registrant (must be pre-created via `contact-domain`) |
| `description` | No | Internal label for the domain |
| `lock` | No | `1` to enable registry lock, `0` to leave unlocked |
| `id_shield` | No | `1` to enable WHOIS privacy (ID Shield), `0` to leave disabled |

**Request:**
```xml
<pointer>
    <domain>
        <create>
            <domain>example.com</domain>
            <duration>1</duration>
            <ns1>ns1.example.com</ns1>
            <ns2>ns2.example.com</ns2>
            <registrant>contact_code</registrant>
            <description>My website</description>
            <lock>1</lock>
            <id_shield>1</id_shield>
        </create>
    </domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 301 (invalid arguments), 303 (insufficient balance), 304 (lock not supported), 305 (ID shield not supported)

---

### Renew Domain

Extends the expiry date of an existing domain. Deducts the renewal fee from the account balance.

| Field | Required | Description |
|-------|----------|-------------|
| `domain` | Yes | Fully qualified domain name (e.g. `example.com`) |
| `duration` | Yes | Renewal period in years |

**Request:**
```xml
<pointer>
    <domain>
        <renew>
            <domain>example.com</domain>
            <duration>1</duration>
        </renew>
    </domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 301, 303 (insufficient balance)

---

### Transfer Domain

Initiates a transfer of a domain from another registrar to Pointer. Requires the domain's EPP/auth code.

| Field | Required | Description |
|-------|----------|-------------|
| `domain` | Yes | Fully qualified domain name to transfer |
| `code` | Yes | EPP authorization code (auth code) provided by the losing registrar |

**Request:**
```xml
<pointer>
    <domain>
        <transfer>
            <domain>example.com</domain>
            <code>EPP-AUTH-CODE</code>
        </transfer>
    </domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 301, 303 (insufficient balance)

---

### Update Domain Nameservers

Replaces the nameservers for a domain. Both `ns1` and `ns2` must be provided; the change propagates to the registry immediately.

| Field | Required | Description |
|-------|----------|-------------|
| `domain` | Yes | Fully qualified domain name |
| `ns1` | Yes | New primary nameserver hostname |
| `ns2` | Yes | New secondary nameserver hostname |

**Request:**
```xml
<pointer>
    <domain>
        <updatens>
            <domain>example.com</domain>
            <ns1>ns1.example.com</ns1>
            <ns2>ns2.example.com</ns2>
        </updatens>
    </domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 301, 307 (nameserver already exists), 308 (nameserver creation failed)

---

### Lock / Unlock Domain

Enables or disables the registry lock on a domain. A locked domain cannot be transferred, deleted, or have its nameservers changed until it is unlocked.

**Lock request:**
```xml
<pointer>
    <domain>
        <lock>
            <domain>example.com</domain>
        </lock>
    </domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Unlock request:**
```xml
<pointer>
    <domain>
        <unlock>
            <domain>example.com</domain>
        </unlock>
    </domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 304 (lock not supported by TLD)

---

### Add / Remove ID Shield

Enables or disables WHOIS privacy (ID Shield) for a domain. When enabled, the registrant's personal details are replaced with proxy contact information in the public WHOIS record.

**Add ID Shield:**
```xml
<pointer>
    <domain>
        <addidshield>
            <domain>example.com</domain>
        </addidshield>
    </domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Remove ID Shield:**
```xml
<pointer>
    <domain>
        <removeidshield>
            <domain>example.com</domain>
        </removeidshield>
    </domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 305 (ID shield not supported by TLD)

---

## Contact Operations

Contacts store the registrant information associated with a domain. Each contact is identified by a unique `code` returned on creation.

### Create Contact

Creates a new contact record. The `tld` field controls which registry-specific validation rules are applied.

| Field | Required | Description |
|-------|----------|-------------|
| `domain` | Yes | Domain label (without TLD) the contact is associated with |
| `name` | Yes | Full name of the contact person or organization |
| `street` | Yes | Street address |
| `city` | Yes | City |
| `sp` | Yes | State / province |
| `pc` | Yes | Postal / ZIP code |
| `country` | Yes | ISO 3166-1 alpha-2 country code (e.g. `GR`) |
| `phone` | Yes | Phone number in ITU format (e.g. `+30.2310123456`) |
| `email` | Yes | Contact email address |
| `tld` | Yes | TLD the contact is intended for (e.g. `com`, `gr`) |

**Request:**
```xml
<pointer>
    <contact-domain>
        <create>
            <domain>example</domain>
            <name>John Doe</name>
            <street>123 Main Street</street>
            <city>Athens</city>
            <sp>Attica</sp>
            <pc>10431</pc>
            <country>GR</country>
            <phone>+30.2310123456</phone>
            <email>john@example.com</email>
            <tld>com</tld>
        </create>
    </contact-domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Response includes** the new `code` that identifies this contact.

**Errors:** 103, 301 (invalid field values)

---

### Get Contact Info

Retrieves all stored fields for an existing contact.

| Field | Required | Description |
|-------|----------|-------------|
| `code` | Yes | Contact code returned from `create` |
| `tld` | Yes | TLD the contact belongs to |

**Request:**
```xml
<pointer>
    <contact-domain>
        <get>
            <code>contact_code</code>
            <tld>com</tld>
        </get>
    </contact-domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 105 (contact does not exist)

---

### Update Contact

Updates the stored fields for an existing contact. Some registries reject changes to contacts that are actively linked to a domain (error 309).

| Field | Required | Description |
|-------|----------|-------------|
| `domain` | Yes | Domain the contact is linked to |
| `code` | Yes | Contact code to update |
| `name` … `email` | Yes | Same fields as `create` — all fields must be provided |
| `tld` | Yes | TLD the contact belongs to |

**Request:**
```xml
<pointer>
    <contact-domain>
        <update>
            <domain>example.com</domain>
            <code>contact_code</code>
            <name>Jane Doe</name>
            <street>456 New Street</street>
            <city>Thessaloniki</city>
            <sp>Central Macedonia</sp>
            <pc>54621</pc>
            <country>GR</country>
            <phone>+30.2310654321</phone>
            <email>jane@example.com</email>
            <tld>com</tld>
        </update>
    </contact-domain>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 301, 309 (registry rejects update for active contact)

---

## Product Operations

Products represent hosting services (web hosting, email, VPS, etc.) associated with a domain.

### Create Product

Provisions a new hosting product. The `code` identifies the product plan (retrieve available codes via `order-products`).

| Field | Required | Description |
|-------|----------|-------------|
| `domain` | Yes | Fully qualified domain name the product is provisioned under |
| `duration` | Yes | Service period in months |
| `code` | Yes | Product plan code |
| `autorenew` | No | `1` to enable automatic renewal, `0` to disable |

**Request:**
```xml
<pointer>
    <product>
        <create>
            <domain>example.com</domain>
            <duration>12</duration>
            <code>product_code</code>
            <autorenew>1</autorenew>
        </create>
    </product>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 301, 302 (product code not found), 303 (insufficient balance)

---

### Upgrade Product

Changes an existing product to a different plan. The new `code` must be in the same product category. Only upgrades (higher-tier plans) are guaranteed to be supported; downgrades depend on the product type.

| Field | Required | Description |
|-------|----------|-------------|
| `id` | Yes | Internal product ID |
| `code` | Yes | Target product plan code |

**Request:**
```xml
<pointer>
    <product>
        <upgrade>
            <id>product_id</id>
            <code>new_product_code</code>
        </upgrade>
    </product>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 302 (product not found), 303 (insufficient balance), 306 (upgrade/downgrade not allowed)

---

### Renew Product

Extends the expiry date of an existing product. Deducts the renewal fee from the account balance.

| Field | Required | Description |
|-------|----------|-------------|
| `id` | Yes | Internal product ID |
| `duration` | Yes | Extension period in months |

**Request:**
```xml
<pointer>
    <product>
        <renew>
            <id>product_id</id>
            <duration>12</duration>
        </renew>
    </product>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 302 (product not found), 303 (insufficient balance)

---

## SSL Operations

### Create SSL Certificate

Issues a new SSL certificate. The CSR must be generated on the server where the certificate will be installed.

| Field | Required | Description |
|-------|----------|-------------|
| `duration` | Yes | Certificate validity in months |
| `code` | Yes | SSL product code (retrieve available codes via `order-products`) |
| `csr` | Yes | Certificate Signing Request (PEM format) |

**Request:**
```xml
<pointer>
    <ssl>
        <create>
            <duration>12</duration>
            <code>ssl_product_code</code>
            <csr>-----BEGIN CERTIFICATE REQUEST-----
...
-----END CERTIFICATE REQUEST-----</csr>
        </create>
    </ssl>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 301 (invalid CSR), 302 (SSL product code not found), 303 (insufficient balance)

---

### Renew SSL Certificate

Renews an existing SSL certificate before it expires.

| Field | Required | Description |
|-------|----------|-------------|
| `id` | Yes | Internal SSL certificate ID |
| `duration` | Yes | Renewal period in months |

**Request:**
```xml
<pointer>
    <ssl>
        <renew>
            <id>ssl_id</id>
            <duration>12</duration>
        </renew>
    </ssl>
    <key>api_key</key>
    <chksum>md5hash</chksum>
</pointer>
```

**Errors:** 103, 302 (SSL certificate not found), 303 (insufficient balance)

---

## Utility / Lookup Operations

These endpoints do not require an API key (`<key>`) but still require a valid `<chksum>`.

### Get Products List

Returns all available product plans and their pricing. Use the returned `code` values when calling `product.create`, `product.upgrade`, or `ssl.create`.

**Request:**
```xml
<pointer>
    <products/>
    <chksum>md5hash</chksum>
</pointer>
```

---

### Get Order Products

Returns available products filtered by category. Categories group related products (e.g. web hosting, email hosting, VPS).

| Field | Required | Description |
|-------|----------|-------------|
| `category` | Yes | Category ID to filter by |

**Request:**
```xml
<pointer>
    <order-products>
        <category>category_id</category>
    </order-products>
    <chksum>md5hash</chksum>
</pointer>
```

---

### Get Addons

Returns available addons for a given product plan. Addons are optional extras that can be provisioned alongside the main product (e.g. extra storage, dedicated IP).

| Field | Required | Description |
|-------|----------|-------------|
| `product` | Yes | Product plan code to retrieve addons for |

**Request:**
```xml
<pointer>
    <addons>
        <product>product_code</product>
    </addons>
    <chksum>md5hash</chksum>
</pointer>
```

---

### Check Domain Availability

Checks whether one or more domain names are available for registration. Multiple TLDs can be checked in a single request.

| Element | Description |
|---------|-------------|
| `domain` | Domain label without TLD (e.g. `example`) — can be repeated |
| `tld` | TLD to check (e.g. `.com`) — can be repeated |

**Request:**
```xml
<pointer>
    <domain-check>
        <domains>
            <domain>example</domain>
            <domain>mysite</domain>
        </domains>
        <tlds>
            <tld>.com</tld>
            <tld>.net</tld>
            <tld>.gr</tld>
        </tlds>
    </domain-check>
    <chksum>md5hash</chksum>
</pointer>
```

**Response** contains an availability status for each domain + TLD combination.

---

## Error Handling

Always check the `<code>` element in the response before processing the payload. A response with `<code>200</code>` indicates success; any other code signals a problem described by `<message>`.

For codes 501 (API error), record the full request XML and contact support — these indicate unexpected server-side failures.

## Rate Limiting

Each client account has a maximum number of concurrent API connections. Exceeding the limit returns code 104. Implement exponential backoff and retry after a short delay.

## Support

For API support contact the system administrator or open a support ticket through the client panel.
