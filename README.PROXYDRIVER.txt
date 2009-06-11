
=  MetaSearch Extension =
==  MsProxyDatabaseDriver Documentation ==

The Proxy driver needs a special web server layout, since it has special
needs to the host setup. When you're mediawiki url is e.g.

   www.example.com/wiki/Bla

You should setup some willdcard subdomain like

  proxy.example.com

that will fully redirect to "proxy.php" in the extension base
directory. That can be done with such a VirtualHost container in
your Apache configuration:


<VirtualHost *:80>
    ServerName proxy.biokemika.svenk.homeip.net
    ServerAlias *.proxy.biokemika.svenk.homeip.net
    DocumentRoot "/mnt/data/Programme/Biokemika/"

    RewriteEngine on
    RewriteRule .*
    /extensions/metasearch/domainproxy.php
</VirtualHost>

That's all the magic. There will be more documentation soon ;-)

Sven Koeppel
12.06.2009
