# Alt Inbound

> Ez IP blocking, whitelist or blackist IPs to control who can access your creation.

## Features

- IP Whitelisting and Blacklisting
- "Blocked" page shown to the user when they get blocked
- Support for returning a custom view when a request is blocked
- CSV Imports and Exports

## How to Install

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

``` bash
composer require alt-design/alt-inbound
```

## Basic usage

Head into your Statamic control panel and find "Alt Inbound" under Alt Tools → Privacy & security on the left sidebar to bring up the controls for the addon.

Just take an IP address and add it at the top, then hit save.

There is a switch between block-list and allow-list mode (see [Mode](#mode)), then you're gravy.

Example IP : 
```
192.168.1.1
```

## Entries

Each entry is a single IPv4 or IPv6 address. Ranges such as `10.0.0.0/8`, wildcards such as `192.168.1.*` and hostnames are rejected.

Addresses are saved in a standard form, and visitors are matched in the same form. IPv6 is saved in lower case and compressed, so `2001:DB8:0:0:0:0:0:1` and `2001:db8::1` are the same entry. An IPv4 address written as IPv4-mapped IPv6 (`::ffff:192.168.1.1`) is saved as IPv4.

### Upgrading from an earlier version

Entries saved by earlier versions keep matching as they did before. One that was not written in standard form, for example an IPv6 address in capitals or an address with a space after it, never matched a visitor and still does not. To fix those, export the list, delete any ranges or wildcards (they never matched anything, and the import rejects them), import the file again, which adds each address in standard form, and then delete the old entries.

The control panel lists these entries first and labels them: **Not a single IP address** for ranges, wildcards and other text, which match no visitor, and **Not saved in standard form** for addresses that may not match. Remove them, and add the addresses again where you still need them.

## Mode

In block-list mode, visitors on the list are sent to the blocked page and everyone else gets through. In allow-list mode, only visitors on the list get through.

The mode can be switched in the control panel, which saves it in `content/alt-inbound`. To fix it in the site's environment instead, set `ALT_INBOUND_MODE` in `.env`:

```env
ALT_INBOUND_MODE=allow
```

- `allow` or `block` sets the mode, and the control panel switch is locked while it is set. Case and surrounding spaces are ignored.
- Left out or empty, the control panel setting is used.
- Any other value counts as `allow`, so a mistyped value sends visitors to the blocked page rather than letting them all in. The control panel shows a warning naming the value.

Use `ALT_INBOUND_MODE=allow` on any site that relies on allow-list mode. A mode saved from the control panel is lost along with `content/alt-inbound` if that folder is not kept between deployments, and the site then lets every visitor in. With the environment variable set, a missing list sends every visitor to the blocked page instead, and the control panel warns that the list is empty. The control panel itself is not covered by the check, so you can still sign in and fix the list.

If the site caches its config (`php artisan config:cache`), run that again after changing the value.

## CSV import and export

The export has an `id` and an `ip` column. A value starting with `=`, `+`, `-`, `@`, a tab or a line break is prefixed with `'`, so a spreadsheet shows it as text rather than running it as a formula. A valid IP address never starts with one of these, so only entries saved by earlier versions are affected.

The import takes a CSV file of up to 1MB and 5,000 rows, with a header row and an `ip` column. Other columns, including `id`, are ignored, and new entries get new ids. Addresses already on the list are left as they are, and empty rows are skipped. Every other row must hold a single IP address: if any does not, nothing is imported and the error names the rows to check.

## Custom blocked page

The custom view must be one of your site's own views, written like `templates.blocked` or `templates/blocked`, and it must exist when you save it. Namespaced views (`vendor::view`) are not accepted. If the saved view is later removed or fails to render, visitors see the default blocked page and the error is reported to your application log.

## Change log

Adding, removing and importing entries, switching between block-list and allow-list mode, and changing the custom view are written to the application log at `info` level, with the id of the control panel user who made the change. IP addresses are not written to the log.

## Where the list is stored

Entries are YAML files under `content/alt-inbound`: one file per address in `blocks/`, plus `whitelist.yaml` while allow-list mode is on and `custom-view.yaml` for the custom view. The entries are visitors' IP addresses, which are personal data.

Most Statamic sites commit `content/` to git. If yours does, every entry goes into the repository's history and stays there after it is deleted from the list. To keep them out, add the folder to `.gitignore` and stop tracking it:

```bash
echo "content/alt-inbound" >> .gitignore
git rm -r --cached content/alt-inbound
```

Entries that were already committed remain in the history.

If you gitignore the folder, it has to survive deployments. With zero-downtime deployments, such as on Laravel Forge or Envoyer, each release is a fresh checkout, so make `content/alt-inbound` a shared path that is linked into every release. Otherwise each new release starts without the folder: the list is empty, and a site in allow-list mode falls back to block-list mode, which lets every visitor in, unless `ALT_INBOUND_MODE=allow` is set (see [Mode](#mode)).

## Deployment notes

- **Proxies and Cloudflare.** Alt Inbound checks the visitor IP that Laravel reports. Behind Cloudflare or a load balancer, that is the proxy's address unless the site's trusted proxies are set: `trustProxies` in `bootstrap/app.php` on Laravel 11 and later, or the `TrustProxies` middleware on Laravel 10 and earlier. Trust only the proxies you actually use. If the site trusts every address and can be reached without going through the proxy, a visitor can set their own IP in the `X-Forwarded-For` header and get past the list.
- **Static caching.** With Statamic's full static caching, the web server serves cached pages without running PHP, so the check does not run for those pages.
- **What the check covers.** It runs on routes in Laravel's `web` middleware group. That includes the Statamic front end while `statamic.routes.middleware` is left at its default of `web`. The control panel, including its login page, and the Statamic REST and GraphQL APIs are not covered, in allow-list mode as well as block-list mode.

## Permissions

Alt Inbound registers a **View and manage Alt Inbound IP blocks** permission (`view alt-inbound`). Super users always have it. Any other user needs a role that grants it, under `Users > Roles`, before the tool appears in their navigation or its pages and actions will load.

Under it sits **Change the Alt Inbound mode and blocked page** (`manage alt-inbound settings`), for the two settings that affect every visitor: the block-list or allow-list mode, and the custom blocked page. A user without it can still view, add, remove, import and export entries, and sees which mode the site is in, but not the switch or the custom view field.

If you are upgrading from a version without these permissions, non-super users who used Alt Inbound lose access until their role is given `view alt-inbound`.

**Upgrading to 1.3.0:** non-super users who switched the mode or set the custom blocked page lose those two controls until their role is also given `manage alt-inbound settings`. Their access to the list is unchanged.

## Testing

```bash
composer install
./vendor/bin/pest
```

## Questions etc

Drop us a big shout-out if you have any questions, comments, or concerns. We're always looking to improve our addons, so if you have any feature requests, we'd love to hear them.

Also - check out our other addons!
- [Alt Redirect Addon](https://github.com/alt-design/Alt-Redirect-Addon)
- [Alt Sitemap Addon](https://github.com/alt-design/Alt-Sitemap-Addon)
- [Alt Akismet Addon](https://github.com/alt-design/Alt-Akismet-Addon)
- [Alt Password Protect Addon](https://github.com/alt-design/Alt-Password-Protect-Addon)
- [Alt Cookies Addon](https://github.com/alt-design/Alt-Cookies-Addon)
- [Alt Inbound Addon](https://github.com/alt-design/Alt-Inbound-Addon)

## Postcardware

Send us a postcard from your hometown if you like this addon. We love getting mail from other cool peeps!

Alt Design  
St Helens House  
Derby  
DE1 3EE  
UK    

