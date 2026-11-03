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

Head into your Statamic control panel and find "Alt Inbound" under Tools on the left sidebar to bring up the controls for the addon.

Just take an IP address and add it at the top, then hit save.

There is a toggle to toggle between whitelist and blacklist, then you're gravy

Example IP : 
```
192.168.1.1
```

## Entries

Each entry is a single IPv4 or IPv6 address. Ranges such as `10.0.0.0/8`, wildcards such as `192.168.1.*` and hostnames are rejected.

Addresses are saved in a standard form, and visitors are matched in the same form. IPv6 is saved in lower case and compressed, so `2001:DB8:0:0:0:0:0:1` and `2001:db8::1` are the same entry. An IPv4 address written as IPv4-mapped IPv6 (`::ffff:192.168.1.1`) is saved as IPv4.

### Upgrading from 2.0.2 or earlier

Entries saved by earlier versions are matched on their exact text. One that was not written in standard form, for example an IPv6 address in capitals or an address with a space after it, still does not match. To re-save them, export the list and import it again. Delete any ranges or wildcards first: they never matched anything, and the import rejects them.

## CSV import and export

The export has an `id` and an `ip` column. A value starting with `=`, `+`, `-`, `@`, a tab or a line break is prefixed with `'`, so a spreadsheet shows it as text rather than running it as a formula. A valid IP address never starts with one of these, so only entries saved by earlier versions are affected.

The import takes a CSV file of up to 1MB with a header row and an `ip` column. Other columns, including `id`, are ignored, and new entries get new ids. Addresses already on the list are left as they are. Every row must hold a single IP address: if any does not, nothing is imported and the error names the rows to check.

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

If you gitignore the folder, it has to survive deployments. With zero-downtime deployments, such as on Laravel Forge or Envoyer, each release is a fresh checkout, so make `content/alt-inbound` a shared path that is linked into every release. Otherwise each new release starts without the folder: the list is empty, and a site in allow-list mode falls back to block-list mode, which lets every visitor in.

## Deployment notes

- **Proxies and Cloudflare.** Alt Inbound checks the visitor IP that Laravel reports. Behind Cloudflare or a load balancer, that is the proxy's address unless the site's trusted proxies are set: `trustProxies` in `bootstrap/app.php` on Laravel 11 and later, or the `TrustProxies` middleware on Laravel 10. Trust only the proxies you actually use. If the site trusts every address and can be reached without going through the proxy, a visitor can set their own IP in the `X-Forwarded-For` header and get past the list.
- **Static caching.** With Statamic's full static caching, the web server serves cached pages without running PHP, so the check does not run for those pages.
- **What the check covers.** It runs on routes in Laravel's `web` middleware group, which includes the Statamic front end. The control panel and the Statamic REST API (which uses the `api` group by default) are not covered.

## Permissions

Alt Inbound registers a **View and manage Alt Inbound IP blocks** permission (`view alt-inbound`). Super users always have it. Any other user needs a role that grants it, under `Users > Roles`, before the tool appears in their navigation or its pages and actions will load.

If you are upgrading from a version without this permission, non-super users who used Alt Inbound lose access until their role is given it.

## Testing

```bash
composer install
./vendor/bin/pest
```

## Questions etc

Drop us a big shout-out if you have any questions, comments, or concerns. We're always looking to improve our addons, so if you have any feature requests, we'd love to hear them.

### Starter Kits
- [Alt Starter Kit](https://statamic.com/starter-kits/alt-design/alt-starter-kit) 

### Addons
- [Alt Redirect Addon](https://github.com/alt-design/Alt-Redirect-Addon)
- [Alt Sitemap Addon](https://github.com/alt-design/Alt-Sitemap-Addon)
- [Alt Akismet Addon](https://github.com/alt-design/Alt-Akismet-Addon)
- [Alt Password Protect Addon](https://github.com/alt-design/Alt-Password-Protect-Addon)
- [Alt Cookies Addon](https://github.com/alt-design/Alt-Cookies-Addon)
- [Alt Inbound Addon](https://github.com/alt-design/Alt-Inbound-Addon)
- [Alt Google 2FA Addon](https://github.com/alt-design/Alt-Google-2fa-Addon)

## Postcardware

Send us a postcard from your hometown if you like this addon. We love getting mail from other cool peeps!

Alt Design  
St Helens House  
Derby  
DE1 3EE  
UK    

