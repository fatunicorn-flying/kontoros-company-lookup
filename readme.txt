=== Kontoros Company Lookup for WooCommerce ===
Contributors: WPORG_USERNAME
Tags: woocommerce, checkout, company, vat, lithuania
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 8.9
WC tested up to: 11.3
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lithuanian company autocomplete for the WooCommerce checkout: company name, company code, VAT code and registered address in one click.

== Description ==

Business customers type the first letters of their company name, or the first digits of the company code, and pick their company from a list. The plugin fills in the rest of the checkout form:

* company name (short form such as UAB "Jungtis", or the official register name)
* company code (juridinio asmens kodas)
* VAT code (only if the company is currently a VAT payer)
* registered address: street and house number, city, county (apskritis), postcode and country

No more typos in invoice details, and checkout is faster for business customers.

= Features =

* Works with the **block checkout** and the **classic checkout** (shortcode).
* Adds **Company code** and **VAT code** fields to the billing address. They are saved to the order and shown on the order page, in the order admin and in emails. You can turn them off if another plugin already adds these fields.
* Search from the Company field or from the Company code field. Mouse and keyboard friendly (arrow keys, Enter, Escape).
* Shows the Company field if WooCommerce hides it, so the company name is saved with the order (optional).
* Only active companies are suggested by default; deregistered and liquidated companies can be included.
* Lookups go through your own server and are cached for 10 minutes, so no API key is exposed in the browser.
* If the lookup service can't be reached, customers simply type their details by hand. Checkout is never blocked.
* English and Lithuanian included.

Source code and bug reports: [GitHub](https://github.com/GITHUB_USER/kontoros-company-lookup).

= Free to use =

The data comes from [kontoros.lt](https://www.kontoros.lt), a free register of Lithuanian companies built from public data of the Centre of Registers (Registrų centras), the State Tax Inspectorate (VMI) and Sodra. No account or API key is needed. Without a key a shop can make 100 lookups per 5 minutes and 1000 per day, which is plenty for a normal shop. A free key with higher limits is available on request.

== External services ==

This plugin connects to the kontoros.lt company search API to find Lithuanian companies. Without it the autocomplete can't work.

* **What is sent and when:** when a customer types at least 2 characters into the Company or Company code field on the checkout, your server sends that text to `https://www.kontoros.lt/api/v1/companies/search`, together with your site address (in the User-Agent header) and your API key if you entered one. Results are cached on your server for 10 minutes, so repeated searches are not sent again.
* **What is not sent:** no personal data of the customer (name, email, phone, address, IP address or order details).
* **Service provider:** kontoros.lt. [Terms of use and privacy policy](https://www.kontoros.lt/privatumo-politika), [API documentation](https://www.kontoros.lt/api).

== Installation ==

1. Install the plugin from Plugins → Add New (search for "Kontoros"), or upload the ZIP file, and activate it. WooCommerce must be active.
2. Go to **WooCommerce → Settings → Kontoros** to review the options. The defaults suit most shops.
3. Open your checkout and type a company name into the Company or Company code field.

== Frequently Asked Questions ==

= Do I need an API key? =

No. Leave the field empty. Add a key only if you were given one for higher limits.

= Which companies can be found? =

All legal entities registered in Lithuania: companies (UAB, AB, MB), sole proprietorships (IĮ), public institutions (VšĮ), associations and others.

= Does it work with the block checkout? =

Yes. The plugin registers its fields with the WooCommerce Additional Checkout Fields API and fills the block checkout through the WooCommerce data store. The classic shortcode checkout is supported too.

= Where are the company code and VAT code stored? =

In the order. For block checkout orders WooCommerce stores them as additional checkout fields. Classic checkout orders store them in the same way and also as `_billing_company_code` and `_billing_vat_code` order meta, for invoicing plugins.

= Why is the postcode filled without "LT-"? =

WooCommerce stores "LT-12345" as "LT12345", which the block checkout then rejects as invalid. Five digits are accepted everywhere.

= The suggestions don't appear =

Check that the Company or Company code field is visible on the checkout and that your server can make outgoing HTTPS requests to www.kontoros.lt.

== Screenshots ==

1. Suggestions while typing a company name on the block checkout.
2. The checkout form after picking a company: address, county, postcode, company code and VAT code are filled in.
3. Settings under WooCommerce → Settings → Kontoros.
4. Company code and VAT code on the order in the admin.

== Changelog ==

= 1.1.0 =
* First public release on WordPress.org.
* Tested with WordPress 7.1 and WooCommerce 11.3.
* The address option description mentions the county.

= 1.0.4 =
* Classic checkout orders show the company code and VAT code on block theme order pages, in the admin and in emails, the same way as block checkout orders.

= 1.0.3 =
* Show the Company field when WooCommerce hides it, so the company name is saved with the order (setting, on by default).

= 1.0.2 =
* Search also from the Company code field.
* Fill the billing address too when "use same address for billing" is ticked.
* Postcode filled as 5 digits, which WooCommerce accepts in both checkouts.
* Wider suggestion list on narrow fields.

= 1.0.1 =
* Fill the county (Apskritis) field, which WooCommerce requires for Lithuanian addresses.

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.1.0 =
First WordPress.org release. No changes to your settings are needed.
