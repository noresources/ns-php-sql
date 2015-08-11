<?xml version="1.0" encoding="UTF-8"?>
<!-- Copyright © 2011-2012 by Renaud Guillard (dev@nore.fr) -->
<!-- Distributed under the terms of the MIT License, see LICENSE -->
<!-- Transforms sqlDatasource xml document into SQLite instructions -->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sql="http://xsd.nore.fr/sql" version="1.0">

	<xsl:import href="./home/renaud/Projects/ns-php/ns/sql/xsl/2.1"/>

	<xsl:output method="text" indent="yes" encoding="utf-8"/>

	<xsl:strip-space elements="*"/>

	<!-- Template functions -->

	<!-- Protect string -->
	<xsl:template name="sql.protectString">
		<xsl:param name="string"/>
		<xsl:text>'</xsl:text>
		<xsl:call-template name="str.replaceAll">
			<xsl:with-param name="text">
				<xsl:value-of select="$string"/>
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
		<xsl:param name="dataTypeNode"/>
		<xsl:choose>
			<xsl:when test="$dataTypeNode/sql:boolean">
				<xsl:text>NUMERIC</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:numeric">
				<xsl:choose>
					<xsl:when test="dataTypeNode/@decimals">
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

	<!-- /////////////////////////////////////////////////////////////// -->

	<!-- Table primary key constraint -->
	<!-- Do not write constraints if single field + autoincrement type -->
	<!-- This constraint is handled separately -->
	<xsl:template match="sql:table/sql:primarykey">
		<xsl:variable name="singleField" select="count(sql:field) = 1"/>
		<xsl:variable name="firstFieldName" select="sql:field[1]/@name"/>
		<xsl:variable name="firstField" select="../sql:field[@name = $firstFieldName]"/>
		<xsl:variable name="isAutoIncrement" select="$firstField/sql:datatype/sql:numeric[@autoincrement = 'true']"/>
		<xsl:if test="not($singleField and $isAutoIncrement)">
			<xsl:call-template name="sql.tablePrimaryKeyConstraint"/>
		</xsl:if>
	</xsl:template>
	
	<xsl:template match="sql:table/sql:field">
		<xsl:call-template name="sql.elementName"/>
		<xsl:apply-templates/>
				
		<xsl:variable name="name" select="@name"/>
		
		<xsl:variable name="pk" select="../sql:primarykey"/>
		<xsl:variable name="isAutoIncrement" select="(./sql:datatype/sql:numeric/@autoincrement = 'true')"/>
		<xsl:if test="$pk and (count($pk/sql:field) = 1)  and $pk/sql:field[1][@name = $name] and $isAutoIncrement">
			<xsl:text> PRIMARY KEY</xsl:text>
			<xsl:if test="$pk/sql:field[1]/@order">
				<xsl:text> </xsl:text>
				<xsl:value-of select="$pk/sql:field[1]/@order"/>
			</xsl:if>
			<!-- @todo conflict clause -->
			<xsl:text> AUTOINCREMENT</xsl:text>
		</xsl:if>
		
	</xsl:template>

	<!-- /////////////////////////////////////////////////////////////// -->

	<!-- Find a less xpathish solution -->
	<!-- <xsl:template name="tableReference">
		<xsl:param name="id" />
		<xsl:param name="fullName" select="false()" />
		<xsl:if test="$fullName">
		<xsl:if test="//sql:table[@id=$id]/../@name">
		<xsl:call-template name="sql.elementName">
		<xsl:with-param name="name">
		<xsl:value-of select="//sql:table[@id=$id]/../@name" />
		</xsl:with-param>
		</xsl:call-template>
		<xsl:text>.</xsl:text>
		</xsl:if>
		</xsl:if>
		<xsl:call-template name="sql.elementName">
		<xsl:with-param name="name">
		<xsl:value-of select="//sql:table[@id=$id]/@name" />
		</xsl:with-param>
		</xsl:call-template>
		</xsl:template> -->

</xsl:stylesheet>
