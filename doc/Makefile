# $Id: Makefile,v 1.13 2008/03/07 13:01:55 ggiunta Exp $

### USER EDITABLE VARS ###

WEB=/var/www/xmlrpc/doc

MKDIR=mkdir

PHP=php

FOP=fop

PHPDOC=phpdoc


#### DO NOT TOUCH FROM HERE ONWARDS ###

install:
	${MKDIR} -p ${WEB}
	cp *.html ${WEB}
	cp *.css ${WEB}
	cp *.gif ${WEB}
	@echo HTML version of the manual has been installed to ${WEB}


### the following targets are to be used for library development ###

doc: out/index.html xmlrpc_php.pdf javadoc-out/index.html

# tools currently used in building docs: php 5 with xsl extension, apache fop, phpdocumentor
# alternatives include doxygen, jade, saxon, xsltproc etc...

out/index.html xmlrpc_php.pdf: xmlrpc_php.xml
	-${MKDIR} out
# Jade cmd yet to be rebuilt, starting from xml file and putting output in ./out dir, e.g.
#	jade -t xml -d custom.dsl xmlrpc_php.xml
#
# convertdoc command for xmlmind xxe editor
#	convertdoc docb.toHTML xmlrpc_php.xml -u out
#
# saxon + xerces xml parser + saxon extensions + xslthl: adds a little syntax highligting
# (bold and italics only, no color) for php source examples...
#	java \
#	-classpath c:\programmi\saxon\saxon.jar\;c:\programmi\saxon\xslthl.jar\;c:\programmi\xerces\xercesImpl.jar\;C:\htdocs\xmlrpc_cvs\docbook-xsl\extensions\saxon65.jar \
#	-Djavax.xml.parsers.DocumentBuilderFactory=org.apache.xerces.jaxp.DocumentBuilderFactoryImpl \
#	-Djavax.xml.parsers.SAXParserFactory=org.apache.xerces.jaxp.SAXParserFactoryImpl \
#	-Dxslthl.config=file:///c:/htdocs/xmlrpc_cvs/docbook-xsl/highlighting/xslthl-config.xml \
#	com.icl.saxon.StyleSheet -o xmlrpc_php.fo.xml xmlrpc_php.xml custom.fo.xsl use.extensions=1
#
#	custom php script that does the xslt magic
	${PHP} convert.php xmlrpc_php.xml custom.xsl out/
#	post process html files to highlight php code samples
	${PHP} highlight.php out
#	convert to fo and then to pdf using apache fop
	${PHP} convert.php xmlrpc_php.xml custom.fo.xsl xmlrpc_php.fo.xml
	${FOP} xmlrpc_php.fo.xml xmlrpc_php.pdf
#	-rm xmlrpc_php.fo.xml

javadoc-out/index.html: ../lib/xmlrpc.inc ../lib/xmlrpcs.inc ../lib/xmlrpc_wrappers.inc
# generate docs out of javadoc via doxygen
#	doxygen phpxmlrpc.dox
#
#	generate docs out of javadoc via phpdocumentor
	${PHPDOC} -f ../lib/xmlrpc.inc,../lib/xmlrpcs.inc,../lib/xmlrpc_wrappers.inc -t javadoc-out -ti PHP-XMLRPC -o HTML:frames:default -s

dist: doc
	${MKDIR} -p ../xmlrpc-${VERSION}/doc
	-cp out/*.html ../xmlrpc-${VERSION}/doc
	-cp out/*.css ../xmlrpc-${VERSION}/doc
	-cp out/*.gif ../xmlrpc-${VERSION}/doc
	-cp out/*.pdf ../xmlrpc-${VERSION}/doc
	cp xmlrpc_php.xml ../xmlrpc-${VERSION}/doc
	cp xmlrpc_php.pdf ../xmlrpc-${VERSION}/doc
	cp Makefile ../xmlrpc-${VERSION}/doc

clean:
	-rm -f out/*.html
	-rm -rf javadoc-out
	-rm xmlrpc_php.fo.xml
	-rm xmlrpc_php.pdf
