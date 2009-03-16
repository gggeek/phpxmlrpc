# Makefile for phpxmlrpc library
# $Id: Makefile,v 1.37 2008/03/06 22:37:19 ggiunta Exp $

### USER EDITABLE VARS ###

# path to PHP executable, preferably CLI version
PHP=/usr/local/bin/php

# path were xmlrpc lib files will be copied to
PHPINCLUDEDIR=/usr/local/lib/php

# mkdir is a thorny beast under windows: make sure we can not use the cmd version, running eg. "make MKDIR=mkdir.exe"
MKDIR=mkdir


#### DO NOT TOUCH FROM HERE ONWARDS ###

# recover version number from code
# thanks to Firman Pribadi for unix command line help
export VERSION=$(shell egrep "\$GLOBALS *\[ *'xmlrpcVersion' *\] *= *'" xmlrpc.inc | sed -r s/"(.*= *' *)([0-9a-zA-Z.-]+)(.*)"/\\2/g )

LIBFILES=xmlrpc.inc xmlrpcs.inc xmlrpc_wrappers.inc compat/*.php

EXTRAFILES=test.pl \
 test.py \
 rsakey.pem \
 workspace.testPhpServer.fttb

DEMOFILES=vardemo.php \
 demo1.txt \
 demo2.txt \
 demo3.txt

DEMOSFILES=discuss.php \
 server.php \
 proxy.php

DEMOCFILES=agesort.php \
 client.php \
 comment.php \
 introspect.php \
 mail.php \
 simple_call.php \
 which.php \
 wrap.php \
 zopetest.php

TESTFILES=testsuite.php \
 benchmark.php \
 parse_args.php \
 phpunit.php \
 verify_compat.php \
 PHPUnit/*.php

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
	cp --parents ${DEMOFILES} xmlrpc-${VERSION}/demo
	cp --parents ${DEMOCFILES} xmlrpc-${VERSION}/demo/client
	cp --parents ${DEMOSFILES} xmlrpc-${VERSION}/demo/server
	${MKDIR} xmlrpc-${VERSION}/test
	${MKDIR} xmlrpc-${VERSION}/test/PHPUnit
	cp --parents ${TESTFILES} xmlrpc-${VERSION}/test
	${MKDIR} xmlrpc-${VERSION}/extras
	cp --parents ${EXTRAFILES} xmlrpc-${VERSION}/extras
	${MKDIR} xmlrpc-${VERSION}/lib
	${MKDIR} xmlrpc-${VERSION}/lib/compat
	cp --parents ${LIBFILES} xmlrpc-${VERSION}/lib
	${MKDIR} xmlrpc-${VERSION}/debugger
	cp ${DEBUGGERFILES} xmlrpc-${VERSION}/debugger
	cp ${INFOFILES} xmlrpc-${VERSION}
	cd doc && $(MAKE) dist
	find xmlrpc-${VERSION} -type f ! -name "*.fttb" ! -name "*.pdf" ! -name "*.gif" -exec dos2unix {} \;
	-rm xmlrpc-${VERSION}.zip xmlrpc-${VERSION}.tar.gz
	tar -cvf xmlrpc-${VERSION}.tar xmlrpc-${VERSION}
	gzip xmlrpc-${VERSION}.tar
	zip -r xmlrpc-${VERSION}.zip xmlrpc-${VERSION}

doc:
	cd doc && $(MAKE) doc

clean:
	rm -rf xmlrpc-${VERSION} xmlrpc-${VERSION}.zip xmlrpc-${VERSION}.tar.gz
	cd doc && $(MAKE) clean
