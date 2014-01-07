Wordpress plugin for Opauth
===========================

Wordpress plugin for [Opauth](https://github.com/uzyn/opauth).

Opauth is a multi-provider authentication framework.

Requirements
---------
1. Wordpress
2. Permalinks enabled

How to use
----------
1. Install this plugin in your wordpress plugin directory
	```bash
	cd wp-contents/plugins
	git clone https://github.com/redelivre/wp-opauth.git
	```

2. Download the submodule
	```bash
	cd wp-opauth
	git submodule init
	git submodule update
	```

3. Create or copy a configuration file to the plugin root.
	```bash
	cp opauth/example/opauth.conf.php.default opauth.conf.php
	```

4. Download and configure the strategies. Place them in
	opauth/lib/Opauth/Strategy. The callback urls are prefixed by
	PLUGIN_URL/PLUGIN_DIRECTORY/auth. For example:
	http://example.com/wp-content/plugins/wp-opauth/auth/strategy/cb

5. Enable the plugin in the admin panel.

Multisite
---------
This plugin does support multisite, however the strategy has to support
the state parameter and use POST instead of sessions. This is true for most of
the currently available strategies.

License
---------
The MIT License

Copyright (c) 2014
Laboratório de Cultura Digital (http://labculturadigital.org)
