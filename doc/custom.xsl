<?xml version='1.0'?>
<xsl:stylesheet  
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<!--
 Customization xsl stylesheet for docbook to chunked html transform
 @version $Id: custom.xsl,v 1.3 2008/03/06 18:58:44 ggiunta Exp $
 @author Gaetano Giunta
 @copyright (c) 2007-2009 G. Giunta
 @license
-->


<!-- import base stylesheet -->
<xsl:import href="../../../docbook-xsl/xhtml/chunk.xsl"/>


<!-- customization vars -->
<xsl:param name="draft.mode">no</xsl:param>
<xsl:param name="funcsynopsis.style">ansi</xsl:param>
<xsl:param name="html.stylesheet">xmlrpc.css</xsl:param>
<xsl:param name="id.warnings">0</xsl:param>


<!-- elements added / modified -->

<!-- space between function name and opening parenthesis -->
<xsl:template match="funcdef" mode="ansi-nontabular">
  <code>
    <xsl:apply-templates select="." mode="class.attribute"/>
    <xsl:apply-templates mode="ansi-nontabular"/>
    <xsl:text> ( </xsl:text>
  </code>
</xsl:template>

<!-- space between return type and function name -->
<xsl:template match="funcdef/type" mode="ansi-nontabular">
  <xsl:apply-templates mode="ansi-nontabular"/>
  <xsl:text> </xsl:text>
</xsl:template>

<!-- space between last param and closing parenthesis, remove tailing semicolon -->
<xsl:template match="void" mode="ansi-nontabular">
  <code>void )</code>
</xsl:template>

<xsl:template match="varargs" mode="ansi-nontabular">
  <xsl:text>...</xsl:text>
  <code> )</code>
</xsl:template>

<xsl:template match="paramdef" mode="ansi-nontabular">
  <xsl:apply-templates mode="ansi-nontabular"/>
  <xsl:choose>
    <xsl:when test="following-sibling::*">
      <xsl:text>, </xsl:text>
    </xsl:when>
    <xsl:otherwise>
      <code> )</code>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- param types get code formatted (leave a space after type, as it is supposed to be before param name) -->
<xsl:template match="paramdef/type" mode="ansi-nontabular">
  <xsl:choose>
    <xsl:when test="$funcsynopsis.decoration != 0">
      <code>
        <xsl:apply-templates mode="ansi-nontabular"/>
      </code>
    </xsl:when>
    <xsl:otherwise>
      <code>
	<xsl:apply-templates mode="ansi-nontabular"/>
      </code>
    </xsl:otherwise>
  </xsl:choose>
  <xsl:text> </xsl:text>
</xsl:template>

<!-- default values for function parameters -->
<xsl:template match="paramdef/initializer" mode="ansi-nontabular">
  <xsl:text> = </xsl:text>
  <xsl:apply-templates mode="ansi-nontabular"/>
</xsl:template>


</xsl:stylesheet>