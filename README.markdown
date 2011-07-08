## URL Router ##

Version: 1.0

Author: [Symphony Team]

Build Date: 2011-07-08

Requirements: Symphony 2.2

### Installation ###

If the older v0.x router is installed, it must be uninstalled first. This is unfortunate, and a workaround is being searched for. Make sure you copy down your routes before doing this, and re-enter them in the new router.

1. Upload the files into a folder named "url_router" in your Symphony 'extensions' folder.

2. Enable it by selecting "URL Router" on the "System -> Extensions" page, choosing "Enable" from the with-selected menu, and clicking "Apply".

3. Add your rules to the "System -> Preferences" page.

### Notes ###

The upgrade to 1.0 **breaks the installation of the old version** which must be uninstalled before installing this version.

### Changelog ###

1.0 ([John Porter](http://designermonkey.co.uk), [Nick Dunn](http://nick-dunn.co.uk))

* Update as Symphony Team takes ownership.
* Renamed extension class and folder to url_router.
* Manually merged @nickdunn's changes.

0.5:

* Symphony 2.2 compatiability update

0.4 ([John Porter](http://designermonkey.co.uk)):

* Applied patches from pull requests to fix various issues including save failure when no redirects entered and pass-by-reference warning

0.3 ([Max Wheeler](http://makenosound.com)):

* Remove `router.js` as it's no longer needed
* Fix error in save logic, no longer tries to write to `config.php`

0.2 ([Max Wheeler](http://makenosound.com)):

* Only add `router.js` to the `/system/preferences/` page (was breaking other JS)
* Updated README with correct installation details

0.1:

* Initial release, brief documentation forthcoming.
