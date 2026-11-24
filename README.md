# Kontoros Company Lookup for WooCommerce

Lithuanian company autocomplete for the WooCommerce checkout. Customers type a company name or code, pick their company from the list, and the company code, VAT code and registered address (street, city, county, postcode) are filled in.

- Works with the block checkout and the classic checkout
- Adds Company code and VAT code fields, saved to the order and shown in the admin, on the order page and in emails
- Free, no API key needed. Data comes from [kontoros.lt](https://www.kontoros.lt), which is built from public data of Registrų centras, VMI and Sodra

**Install:** from WordPress.org (search "Kontoros" under Plugins → Add New), or download the zip from [kontoros.lt/api](https://www.kontoros.lt/api#woocommerce) or from this repository's Releases.

The user documentation is in [readme.txt](readme.txt), which is also the WordPress.org page.

## What is sent to kontoros.lt

When a customer types at least 2 characters into the Company or Company code field, the shop's server (not the browser) sends that text to `https://www.kontoros.lt/api/v1/companies/search`, with the site address in the User-Agent and the optional API key. No customer personal data is sent. Results are cached for 10 minutes. See [`includes/class-api.php`](includes/class-api.php).

## Layout

| Path | What |
|---|---|
| `kontoros-company-lookup.php` | Plugin bootstrap, HPOS and block checkout compatibility |
| `includes/class-settings.php` | WooCommerce → Settings → Kontoros |
| `includes/class-api.php` | REST proxy `/wp-json/kontoros/v1/search`, caching, per-visitor throttle |
| `includes/class-checkout.php` | Checkout fields (classic and block), order display |
| `assets/checkout.js` | Autocomplete. No dependencies; fills the classic form or the block checkout store |
| `languages/` | `.pot` template and the Lithuanian translation |
| `.wordpress-org/` | Banners, icons and screenshots for the WordPress.org page (not shipped) |

## Development

```bash
php bin/build-zip.php        # build/kontoros-company-lookup-<version>.zip, honouring .distignore
```

Releasing is described in [RELEASING.md](RELEASING.md). In short: bump the version, push a `vX.Y.Z` tag, and GitHub Actions deploys to WordPress.org.

## Contributing

Bug reports and pull requests are welcome. For questions about the data itself (a wrong address, a missing company), use the [kontoros.lt contact form](https://www.kontoros.lt/kontaktai).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
