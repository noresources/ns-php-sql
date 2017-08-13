<?xml version="1.0" encoding="UTF-8"?>
<!-- Copyright © 2011-2012 by Renaud Guillard (dev@nore.fr) -->
<!-- Distributed under the terms of the MIT License, see LICENSE -->

<!-- Transforms sqldatasource xml document into SQLite instructions -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sql="http://xsd.nore.fr/sql">

	<xsl:import href="dbms-base.xsl" />

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

	<!-- Convert generic data types into SQLite type affinity See http://www.sqlite.org/datatype3.html
		# 2.2 Affinity Name Examples -->
	<xsl:template name="sql.dataTypeTranslation">
		<xsl:param name="dataTypeNode" />
		<xsl:choose>
			<xsl:when test="not ($dataTypeNode)" />
			<xsl:when test="$dataTypeNode/sql:boolean">
				<xsl:text>NUMERIC</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:numeric">
				<xsl:variable name="numericNode" select="$dataTypeNode/sql:numeric" />
				<xsl:choose>
					<xsl:when test="$numericNode/@decimals">
						<xsl:text>REAL</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>INTEGER</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:timestamp">
				<xsl:text>NUMERIC</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:string">
				<xsl:text>TEXT</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:binary">
				<xsl:text>BLOB</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<!-- The default will be a string -->
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Table primary key constraint -->
	<!-- Do not write constraints if single column + autoincrement type -->
	<!-- This constraint is handled separately -->
	<xsl:template match="sql:table/sql:primarykey">
		<xsl:if test="count(sql:column) &gt; 1">
			<xsl:call-template name="sql.tablePrimaryKeyConstraint" />
		</xsl:if>
	</xsl:template>

	<xsl:template match="sql:table/sql:column">
		<xsl:apply-templates select="sql:comment" />
		<xsl:call-template name="sql.elementName" />
		<xsl:apply-templates select="*[not (self::sql:comment)]" />

		<xsl:variable name="name" select="@name" />
		<xsl:variable name="pk" select="../sql:primarykey" />
		<xsl:variable name="isAutoIncrement" select="(./sql:datatype/sql:numeric/@autoincrement = 'yes')" />

		<xsl:if test="$pk and (count($pk/sql:column) = 1) and $pk/sql:column[1][@name = $name]">
			<xsl:text> PRIMARY KEY</xsl:text>
			<xsl:if test="$pk/sql:column[1]/@order">
				<xsl:text> </xsl:text>
				<xsl:value-of select="$pk/sql:column[1]/@order" />
			</xsl:if>
			<!-- @todo conflict clause -->
			<xsl:if test="$isAutoIncrement">
				<xsl:text> AUTOINCREMENT</xsl:text>
			</xsl:if>
		</xsl:if>

	</xsl:template>

</xsl:stylesheet>
