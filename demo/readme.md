# PHPXMLRPC demo files

## Installation notes

If you have downloaded these demos separately from the PHPXMLRPC library, and want to see them in action, i.e. actually
test them by executing them, you should make sure that their class autoloading configuration is correct, by either:

1. move the `demo` folder to the root folder of the phpxmlrpc library, i.e. next to `debugger`, `doc`, `lib`, `src`; or
2. edit the files `client/_prepend.php` and `server/_prepend.php` and change the line `include_once __DIR__ . '/../../src/Autoloader.php';`
   to make it point to the location of the phpxmlrpc `src` folder

It goes without saying that the demo files require an installed PHPXMLRPC library to work.

## Usage notes

__NB__ These files are meant for _demo_ purposes. They should _not_ be dumped onto a production web server where they are
directly accessible by the public at large. We take absolutely _no responsibility_ for any consequences if you do that.

## More demos

Please take a look at the demo code in the phpxmlrpc/extras package for more examples of cool Server functionality,
such as having a server generate html documentation for all its xml-rpc methods, act as a reverse proxy, or generate
javascript code to call the xml-rpc methods it exposes.

See: https://github.com/gggeek/phpxmlrpc-extras/tree/master/demo
