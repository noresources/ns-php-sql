<?xml version="1.0" encoding="UTF-8"?>
<!-- Copyright © 2011-2014 by Renaud Guillard (dev@nore.fr) -->
<!-- Distributed under the terms of the MIT License, see LICENSE -->

<!-- Transforms sql:datasource xml document into PostgreSQL instructions -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sql="http://xsd.nore.fr/sql">

	<xsl:import href="base.xsl" />

	<!-- The PostgreSQL target version -->
	<xsl:param name="sql.pgsql.targetVersion" select="9.4" />

	<xsl:output method="text" indent="yes" encoding="utf-8" />

	<xsl:strip-space elements="*" />

	<!-- Template functions -->

	<!-- Protect string -->
	<xsl:template name="sql.protectString">
		<xsl:param name="string" />
		<xsl:text>'</xsl:text>
		<xsl:call-template name="str.replaceAll">
			<xsl:with-param name="text">
				<xsl:value-of select="$string" />
			</xsl:with-param>
			<xsl:with-param name="replace">
				<xsl:text>'</xsl:text>
			</xsl:with-param>
			<xsl:with-param name="by">
				<xsl:text>''</xsl:text>
			</xsl:with-param>
		</xsl:call-template>
		<xsl:text>'</xsl:text>
	</xsl:template>

	<!-- Text translations -->

	<!-- Convert generic data types into PostgreSQL type affinity. --> 
	<!-- See http://www.postgresql.org/docs/9.3/static/datatype.html -->
	<xsl:template name="sql.dataTypeTranslation">
		<xsl:param name="dataTypeNode" />
		<xsl:choose>
			<xsl:when test="$dataTypeNode/sql:boolean">
				<xsl:text>BOOLEAN</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:numeric">
				<xsl:variable name="numericNode" select="$dataTypeNode/sql:numeric" />
				<xsl:choose>
					<xsl:when test="$numericNode/@autoincrement = 'yes'">
						<xsl:text>SERIAL</xsl:text>
					</xsl:when>
					<xsl:when test="$numericNode/@decimals">
						<xsl:text>REAL</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>INTEGER</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:timestamp">
				<xsl:variable name="timestampNode" select="$dataTypeNode/sql:timestamp" />
				<xsl:choose>
					<xsl:when test="$timestampNode/@mode = 'date'">
						<xsl:text>date</xsl:text>
					</xsl:when>
					<xsl:when test="$timestampNode/@mode = 'time'">
						<xsl:text>time</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>timestamp</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:text> with</xsl:text>
				<xsl:if test="not($timestampNode/@timezone = 'yes')">
					<xsl:text>out</xsl:text>
				</xsl:if>
				<xsl:text> time zone</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:string">
				<xsl:text>TEXT</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:binary">
				<xsl:text>BYTEA</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>TEXT</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Objects -->
	
	<xsl:template match="sql:database">
		<xsl:text>CREATE SCHEMA </xsl:text>
		<xsl:if test="$sql.pgsql.targetVersion &gt;= 9.3">
			<xsl:text>IF NOT EXISTS </xsl:text>
		</xsl:if>
		<xsl:call-template name="sql.elementName" />
		<xsl:text>;</xsl:text>
		<xsl:value-of select="$str.endl" />
		<xsl:apply-templates />
	</xsl:template>

</xsl:stylesheet>
