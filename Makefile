# Makefile for phpxmlrpc library
# $Id: Makefile,v 1.37 2008/03/06 22:37:19 ggiunta Exp $

### USER EDITABLE VARS ###

# path to PHP executable, preferably CLI version
PHP=/usr/local/bin/php

# path were xmlrpc lib files will be copied to
PHPINCLUDEDIR=/usr/local/lib/php

# mkdir is a thorny beast under windows: make sure we can not use the cmd version, running eg. "make MKDIR=mkdir.exe"
MKDIR=mkdir

#find too
FIND=find


#### DO NOT TOUCH FROM HERE ONWARDS ###

# recover version number from code
# thanks to Firman Pribadi for unix command line help
#   on unix shells lasts char should be \\2/g )
export VERSION=$(shell egrep "\$GLOBALS *\[ *'xmlrpcVersion' *\] *= *'" lib/xmlrpc.inc | sed -r s/"(.*= *' *)([0-9a-zA-Z.-]+)(.*)"/\2/g )

LIBFILES=lib/xmlrpc.inc lib/xmlrpcs.inc lib/xmlrpc_wrappers.inc lib/compat/*.php

EXTRAFILES=extras/test.pl \
 extras/test.py \
 extras/rsakey.pem \
 extras/workspace.testPhpServer.fttb

DEMOFILES=demo/vardemo.php \
 demo/demo1.txt \
 demo/demo2.txt \
 demo/demo3.txt

DEMOSFILES=demo/server/discuss.php \
 demo/server/server.php \
 demo/server/proxy.php

DEMOCFILES=demo/client/agesort.php \
 demo/client/client.php \
 demo/client/comment.php \
 demo/client/introspect.php \
 demo/client/mail.php \
 demo/client/simple_call.php \
 demo/client/which.php \
 demo/client/wrap.php \
 demo/client/zopetest.php

TESTFILES=test/testsuite.php \
 test/benchmark.php \
 test/parse_args.php \
 test/phpunit.php \
 test/verify_compat.php \
 test/PHPUnit/*.php

INFOFILES=Changelog \
 Makefile \
 NEWS \
 README

DEBUGGERFILES=debugger/index.php \
 debugger/action.php \
 debugger/common.php \
 debugger/controller.php


all: install

install:
	cd lib && cp ${LIBFILES} ${PHPINCLUDEDIR}
	@echo Lib files have been copied to ${PHPINCLUDEDIR}
	cd doc && $(MAKE) install

test:
	cd test && ${PHP} -q testsuite.php


### the following targets are to be used for library development ###

# make tag target: tag existing working copy as release in cvs.
# todo: convert dots in underscore in $VERSION
tag:
	cvs -q tag -p release_${VERSION}

dist: xmlrpc-${VERSION}.zip xmlrpc-${VERSION}.tar.gz

xmlrpc-${VERSION}.zip xmlrpc-${VERSION}.tar.gz: ${LIBFILES} ${DEBUGGERFILES} ${INFOFILES} ${TESTFILES} ${EXTRAFILES} ${DEMOFILES} ${DEMOSFILES} ${DEMOCFILES}
	@echo ---${VERSION}---
	rm -rf xmlrpc-${VERSION}
	${MKDIR} xmlrpc-${VERSION}
	${MKDIR} xmlrpc-${VERSION}/demo
	${MKDIR} xmlrpc-${VERSION}/demo/client
	${MKDIR} xmlrpc-${VERSION}/demo/server
	${MKDIR} xmlrpc-${VERSION}/test
	${MKDIR} xmlrpc-${VERSION}/test/PHPUnit
	${MKDIR} xmlrpc-${VERSION}/extras
	${MKDIR} xmlrpc-${VERSION}/lib
	${MKDIR} xmlrpc-${VERSION}/lib/compat
	${MKDIR} xmlrpc-${VERSION}/debugger
	cp --parents ${DEMOFILES} xmlrpc-${VERSION}
	cp --parents ${DEMOCFILES} xmlrpc-${VERSION}
	cp --parents ${DEMOSFILES} xmlrpc-${VERSION}
	cp --parents ${TESTFILES} xmlrpc-${VERSION}
	cp --parents ${EXTRAFILES} xmlrpc-${VERSION}
	cp --parents ${LIBFILES} xmlrpc-${VERSION}
	cp --parents ${DEBUGGERFILES} xmlrpc-${VERSION}
	cp ${INFOFILES} xmlrpc-${VERSION}
	cd doc && $(MAKE) dist
#   on unix shells last char should be \;
	${FIND} xmlrpc-${VERSION} -type f ! -name "*.fttb" ! -name "*.pdf" ! -name "*.gif" -exec dos2unix {} ;
	-rm xmlrpc-${VERSION}.zip xmlrpc-${VERSION}.tar.gz
	tar -cvf xmlrpc-${VERSION}.tar xmlrpc-${VERSION}
	gzip xmlrpc-${VERSION}.tar
	zip -r xmlrpc-${VERSION}.zip xmlrpc-${VERSION}

doc:
	cd doc && $(MAKE) doc

clean:
	rm -rf xmlrpc-${VERSION} xmlrpc-${VERSION}.zip xmlrpc-${VERSION}.tar.gz
	cd doc && $(MAKE) clean
