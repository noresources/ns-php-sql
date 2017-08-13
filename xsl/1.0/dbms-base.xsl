<?xml version="1.0" encoding="UTF-8"?>
<!-- Copyright © 2011-2012 by Renaud Guillard (dev@nore.fr) -->
<!-- Distributed under the terms of the MIT License, see LICENSE -->

<!-- Common template for sqldatasource xml file processing This stylesheet -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sql="http://xsd.nore.fr/sql">
	<xsl:import href="../../strings.xsl" />
	<xsl:output method="text" indent="yes" encoding="utf-8" />

	<!-- Keyword for NULL values -->
	<xsl:variable name="sql.keyword.dbnull">
		<xsl:text>NULL</xsl:text>
	</xsl:variable>

	<!-- Should be overriden by Datasource implementations -->
	<xsl:template name="sql.protectString">
		<xsl:param name="string" />
		<xsl:value-of select="$string" />
	</xsl:template>

	<!-- Should be overriden by Datasource implementations -->
	<xsl:template name="sql.elementName">
		<xsl:param name="name" select="./@name" />

		<xsl:text>"</xsl:text>
		<xsl:value-of select="normalize-space($name)" />
		<xsl:text>"</xsl:text>
	</xsl:template>

	<xsl:template name="sql.elementNameList">
		<xsl:call-template name="sql.elementName" />
		<xsl:if test="position() != last()">
			<xsl:text>, </xsl:text>
		</xsl:if>
	</xsl:template>

	<!-- Indexed element definition -->
	<xsl:template name="sql.indexedElementName">
		<!-- Indexed element -->
		<xsl:param name="element" select="." />

		<xsl:text>"</xsl:text>
		<xsl:value-of select="normalize-space($element/@name)" />
		<xsl:text>"</xsl:text>

		<xsl:if test="$element/@collation">
			<xsl:text> COLLATE </xsl:text>
			<xsl:value-of select="$element/@collation" />
		</xsl:if>

		<xsl:if test="$element/@order">
			<xsl:text> </xsl:text>
			<xsl:value-of select="$element/@order" />
		</xsl:if>
	</xsl:template>

	<!-- Convert generic data types into datasource type -->
	<!-- Should be overriden by Datasource implementations -->
	<xsl:template name="sql.dataTypeTranslation">
		<xsl:param name="dataTypeNode" />
		<xsl:choose>
			<xsl:when test="not ($dataTypeNode)">
				<xsl:text>TEXT</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:numeric">
				<xsl:text>NUMERIC</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:string">
				<xsl:text>TEXT</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:timestamp">
				<xsl:text>DATETIME</xsl:text>
			</xsl:when>
			<xsl:when test="$dataTypeNode/sql:binary">
				<xsl:text>BINARY</xsl:text>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- Cross data type size (and decimals) specifications -->
	<xsl:template name="sql.dataTypeSizeSpecification">
		<xsl:param name="dataTypeNode" select="." />
		<xsl:variable name="typeNode" select="$dataTypeNode/*[1]" />
		<xsl:if test="$typeNode/@length">
			<xsl:text>(</xsl:text>
			<xsl:value-of select="$typeNode/@length" />
			<xsl:if test="$typeNode/@decimals">
				<xsl:text>,</xsl:text>
				<xsl:value-of select="$typeNode/@decimals" />
			</xsl:if>
			<xsl:text>)</xsl:text>
		</xsl:if>
	</xsl:template>

	<xsl:template name="sql.eventActionTranslation">
		<xsl:param name="action" />
		<xsl:choose>
			<xsl:when test="$action = 'null'">
				<xsl:text>SET NULL</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'cascade'">
				<xsl:text>CASCADE</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'default'">
				<xsl:text>SET DEFAULT</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'noaction'">
				<xsl:text>NO ACTION</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'null'">
				<xsl:text>SET NULL</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'restrict'">
				<xsl:text>RESTRICT</xsl:text>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- Display the name of an existing database table -->
	<!-- Both id or name attribute could be used to reference the table -->
	<!-- id is required to display the full name of the table (db.table) -->
	<xsl:template name="sql.tableReferenceName">
		<xsl:param name="fullName" select="false()" />
		<xsl:param name="name" select="@name" />
		<xsl:param name="id" select="@id" />

		<xsl:choose>
			<xsl:when test="$name">
				<xsl:call-template name="sql.elementName">
					<xsl:with-param name="name" select="normalize-space($name)" />
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="$id">
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
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- /////////////////////////////////////////////////////////////////////// -->

	<xsl:template match="sql:comment">
		<xsl:call-template name="str.prependLine">
			<xsl:with-param name="text" select="." />
			<xsl:with-param name="prependedText" select="'&#45;&#45; '" />
		</xsl:call-template>
		<xsl:value-of select="$str.endl" />
	</xsl:template>

	<xsl:template match="sql:datasource">
		<xsl:text>&#45;&#45; ns-xml database schema to SQL translation</xsl:text>
		<xsl:value-of select="$str.endl" />
		<xsl:text>&#45;&#45; Schema version: </xsl:text>
		<xsl:value-of select="@version" />
		<xsl:value-of select="$str.endl" />
		<xsl:if test="@author">
			<xsl:text>&#45;&#45; Author: </xsl:text>
			<xsl:value-of select="@author" />
			<xsl:value-of select="$str.endl" />
		</xsl:if>
		<xsl:apply-templates select="./*" />
	</xsl:template>

	<xsl:template match="sql:database">
		<xsl:text>&#45;&#45; Database </xsl:text>
		<xsl:value-of select="@name" />
		<xsl:value-of select="$str.endl" />
		<xsl:apply-templates select="sql:comment" />
		<xsl:apply-templates select="sql:table|sql:index" />
	</xsl:template>

	<xsl:template match="sql:database/sql:index">
		<xsl:text>CREATE </xsl:text>
		<xsl:if test="@unique = 'yes'">
			<xsl:text>UNIQUE </xsl:text>
		</xsl:if>
		<xsl:text>INDEX </xsl:text>
		<!-- @todo IF NOT EXISTS -->
		<xsl:if test="../@name">
			<xsl:call-template name="sql.elementName">
				<xsl:with-param name="name" select="../@name" />
			</xsl:call-template>
			<xsl:text>.</xsl:text>
		</xsl:if>
		<xsl:call-template name="sql.elementName" />
		<xsl:text> ON </xsl:text>
		<xsl:apply-templates />
		<xsl:text> (</xsl:text>
		<xsl:for-each select="sql:column">
			<xsl:call-template name="sql.elementNameList" />
		</xsl:for-each>
		<xsl:text>);</xsl:text>
		<xsl:value-of select="$str.endl" />
	</xsl:template>

	<xsl:template match="sql:table">
		<xsl:apply-templates select="./sql:comment" />
		<xsl:text>CREATE TABLE </xsl:text>
		<xsl:if test="../@name">
			<xsl:call-template name="sql.elementName">
				<xsl:with-param name="name">
					<xsl:value-of select="../@name" />
				</xsl:with-param>
			</xsl:call-template>
			<xsl:text>.</xsl:text>
		</xsl:if>
		<xsl:call-template name="sql.elementName" />
		<xsl:value-of select="$str.endl" />
		<xsl:text>(</xsl:text>
		<xsl:value-of select="$str.endl" />

		<!-- List of columns -->
		<xsl:for-each select="sql:column|sql:field">
			<xsl:apply-templates select="." />
			<xsl:if test="position() != last()">
				<xsl:text>,</xsl:text>
				<xsl:value-of select="$str.endl" />
			</xsl:if>
		</xsl:for-each>

		<!-- Primary key -->
		<xsl:if test="sql:primarykey">
			<xsl:variable name="pkText">
				<xsl:apply-templates select="sql:primarykey" />
			</xsl:variable>
			<xsl:if test="string-length($pkText) &gt; 0">
				<xsl:text>,</xsl:text>
				<xsl:value-of select="$str.endl" />
				<xsl:value-of select="$pkText" />
			</xsl:if>
		</xsl:if>

		<!-- Foreign keys -->
		<xsl:if test="sql:foreignkey">
			<xsl:text>,</xsl:text>
			<xsl:for-each select="sql:foreignkey">
				<xsl:value-of select="$str.endl" />
				<xsl:apply-templates select="." />
				<xsl:if test="position() != last()">
					<xsl:text>, </xsl:text>
				</xsl:if>
			</xsl:for-each>
		</xsl:if>
		<xsl:value-of select="$str.endl" />
		<xsl:text>);</xsl:text>
		<xsl:value-of select="$str.endl" />
	</xsl:template>

	<xsl:template match="sql:table/sql:column|sql:table/sql:field">
		<xsl:apply-templates select="sql:comment" />
		<xsl:call-template name="sql.elementName" />
		<xsl:if test="not (sql:datatype)">
			<xsl:text> </xsl:text>
			<xsl:call-template name="sql.dataTypeTranslation"/>
		</xsl:if>
		<xsl:apply-templates select="*[not (self::sql:comment)]" />
	</xsl:template>

	<xsl:template match="sql:index/sql:tableref">
		<xsl:call-template name="sql.tableReferenceName">
			<xsl:with-param name="fullName" select="false()" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:tableref">
		<xsl:call-template name="sql.tableReferenceName">
			<xsl:with-param name="fullName" select="false()" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:reference">
		<xsl:text> REFERENCES </xsl:text>
		<xsl:apply-templates select="sql:tableref" />
		<xsl:if test="sql:column">
			<xsl:text> (</xsl:text>
			<xsl:for-each select="sql:column">
				<xsl:call-template name="sql.elementNameList" />
			</xsl:for-each>
			<xsl:text>)</xsl:text>
			<xsl:apply-templates select="sql:onupdate" />
			<xsl:apply-templates select="sql:ondelete" />
		</xsl:if>
	</xsl:template>

	<xsl:template name="sql.tablePrimaryKeyConstraint">
		<xsl:param name="primaryKeyNode" select="." />

		<xsl:if test="$primaryKeyNode/@name">
			<xsl:text>CONSTRAINT </xsl:text>
			<xsl:call-template name="sql.elementName">
				<xsl:with-param name="name" select="$primaryKeyNode/@name" />
			</xsl:call-template>
			<xsl:text> </xsl:text>
		</xsl:if>

		<xsl:text>PRIMARY KEY (</xsl:text>
		<xsl:for-each select="$primaryKeyNode/sql:column">
			<xsl:call-template name="sql.indexedElementName">
				<xsl:with-param name="element" select="." />
			</xsl:call-template>
			<xsl:if test="position() != last()">
				<xsl:text>, </xsl:text>
			</xsl:if>
		</xsl:for-each>
		<xsl:text>)</xsl:text>
	</xsl:template>

	<!-- Table primary key constraint -->
	<xsl:template match="sql:table/sql:primarykey">
		<xsl:call-template name="sql.tablePrimaryKeyConstraint" />
	</xsl:template>

	<xsl:template match="sql:table/sql:foreignkey">
		<xsl:if test="@name">
			<xsl:text>CONSTRAINT </xsl:text>
			<xsl:call-template name="sql.elementName" />
			<xsl:text>
			</xsl:text>
		</xsl:if>
		<xsl:text>FOREIGN KEY (</xsl:text>
		<xsl:for-each select="sql:column">
			<xsl:call-template name="sql.elementNameList" />
		</xsl:for-each>
		<xsl:text>)</xsl:text>
		<xsl:apply-templates select="sql:reference" />
	</xsl:template>

	<xsl:template match="sql:datatype">
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name">
					<xsl:value-of select="@name" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="sql.dataTypeTranslation">
						<xsl:with-param name="dataTypeNode" select="." />
					</xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:if test="string-length($name) &gt; 0">
			<xsl:text> </xsl:text>
			<xsl:value-of select="normalize-space($name)" />
			<xsl:call-template name="sql.dataTypeSizeSpecification">
				<xsl:with-param name="dataTypeNode" select="." />
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<xsl:template match="sql:default">
		<xsl:text> DEFAULT </xsl:text>
		<xsl:apply-templates />
	</xsl:template>

	<xsl:template match="sql:default/*">
		<xsl:value-of select="." />
	</xsl:template>

	<xsl:template match="sql:default/sql:null">
		<xsl:value-of select="$sql.keyword.dbnull" />
	</xsl:template>

	<xsl:template match="sql:default/sql:string">
		<xsl:call-template name="sql.protectString">
			<xsl:with-param name="string" select="." />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:notnull">
		<xsl:text> NOT NULL</xsl:text>
	</xsl:template>

	<xsl:template match="sql:onupdate">
		<xsl:text> ON UPDATE </xsl:text>
		<xsl:call-template name="sql.eventActionTranslation">
			<xsl:with-param name="action">
				<xsl:value-of select="@action" />
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:ondelete">
		<xsl:text> ON DELETE </xsl:text>
		<xsl:call-template name="sql.eventActionTranslation">
			<xsl:with-param name="action">
				<xsl:value-of select="@action" />
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:text">
		<xsl:call-template name="sql.protectString">
			<xsl:with-param name="string">
				<xsl:value-of select="normalize-strin(.)" />
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

</xsl:stylesheet>
