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

2. Download the submodules
	```bash
	cd wp-opauth
	git submodule init
	git submodule update
	```

3. Configure the plugin via the network administration panel

Multisite
---------
This plugin does support multisite, however the strategy has to support
the state parameter and use POST instead of sessions. This is true for most of
the currently available strategies.

Configuration is done network wise. So different blogs owners don't have to get
difrrent keys and painfully set everything up.

Adding Strategies
---------
Here are the instruction to add a new strategy that isn't yet a submodule.

1. Place it in the strategies directory

2. Update opauth.conf.php's Strategy array.
Make sure the variables you want the user to edit are set to null.

3. If the strategy needs a return url to be configured. Add its suffix
to the array in callbacksuffixes.php. This step is optional, but does help the
user when configuring.

License
---------
The MIT License

Copyright (c) 2014
Laboratório de Cultura Digital (http://labculturadigital.org)
