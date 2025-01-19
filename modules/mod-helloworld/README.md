# This is a Smart.Framework kickstart module


## How to use it:
* create a copy of this module, under the modules directory of Smart.Framework, ex: `mod-website`
* in the new created module you can rename by example: `welcome.php` -> `home.php` and `views/welcome.mtpl.htm` -> `views/home.mtpl.htm`
* update the PHP code in `welcome.php` to load the `home.mtpl.htm` view instead of `welcome.mtpl.htm` as it is now
* now, by accessing the URL at: `?page=website.home` will load your new controller
* for pretty URL routes look at the .htaccess and add your rewrite rules as you want, the examples there show how to rewrite a route like `?page=website.home` to `home.html`
* using a custom main template for your module is optional, you either use a template from `etc/templates/default`, or one in the modules `templates/` folder
* to create a new main template for your your usage you either do it in `etc/templates/{new-name}` or inside your module `templates/` folder

### This is all the **basic usage** stuff, easy ! No headaches ...
If you are not familiar with the MVC code pattern try to get familiar first.
M = Model
V = VIEW
C = Controller

### For **advanced usage** look at the rest of the modules, especially at the `mod-samples` ...
It supports too many features to enumerate here, includding:
	* `Translations` (by default using a YAML backend) but they can be easy extended to use a database backend (see the module: `mod-transl-repo` from `Smart.Framework.Modules` on Github),
	* `PageBuilder` (a website page content manager where you can create your content using many syntax types: `HTML`, `Markdown` or `Text`)
	* `Administration` area, requires authentication ; to enable/disable 2FA look at: `etc/init.php`
	* `Task` area, requires authentication, restricted also by a particular IP address set in `etc/init.php`


## Hints
To serve HTTP2 or HTTP3 use this scenario (example): run HAProxy on port 80 and 443.
Run Apache, PHP and Smart.Framework behind HAProxy on a high port like: 12080 for http and 12443 for https.
If you run the Smart.Framework behind a proxy like the above scenario look to the `etc/init.php` settings and enable proxy mode IP detection otherwise instead having the real IP of the visitors you will have the IP address of your proxy ;-) ...
This configuration is tested in very high loads, with high peak web traffic, like serving 1+ million visitors per day with just 3 servers.
Also the security part had no any issues in more than 10 years.


## Security
This framework is designed with the **Apache HTTP server** security in mind.
Under Apache the security is ensured by `.htaccess` files.
You should tailor your URL rewrite rules using the same `.htaccess` which is a standard, look at the commented rules.
If you create new folders under Smart.Framework and you need to restrict the world / web access to those, create inside those folders a `.htaccess` file with the following content:
```
# Deny Access: Apache 2.2
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
</IfModule>
# Deny Access: Apache 2.4
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
```

