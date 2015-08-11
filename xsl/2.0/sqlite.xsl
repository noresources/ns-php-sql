<?xml version="1.0" encoding="UTF-8"?>
<!-- Copyright © 2011-2012 by Renaud Guillard (dev@nore.fr) -->
<!-- Distributed under the terms of the MIT License, see LICENSE -->
<!-- Transforms sqlDatasource xml document into SQLite instructions -->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sql="http://xsd.nore.fr/sql" version="1.0">

	<!--xsl:import: /home/renaud/Projects/ns-xml/ns/xsl/sql/2.0/base.xsl-->

	<!--xsl:import: /home/renaud/Projects/ns-xml/ns/xsl/sql/2.0/../../strings.xsl-->

	<!-- UNIX-style end of line character (LF) -->
	<xsl:variable name="str.unix.endl">
		<xsl:value-of select="'&#10;'"/>
	</xsl:variable>

	<!-- MacOS-style end of line character (CR) -->
	<xsl:variable name="str.mac.endl">
		<xsl:value-of select="'&#13;'"/>
	</xsl:variable>

	<!-- Windows-style end of line character (CRLF) -->
	<xsl:variable name="str.windows.endl">
		<xsl:value-of select="'&#13;&#10;'"/>
	</xsl:variable>

	<!-- Default End-of-line character(s) -->
	<xsl:param name="str.endl">
		<xsl:value-of select="$str.unix.endl"/>
	</xsl:param>

	<xsl:param name="str.blanks" select="'&#9; '"/>

	<!-- Indicates if text contains blank chars -->
	<xsl:template name="str.isBlank">
		<!-- Text to check -->
		<xsl:param name="text"/>
		<!-- Reserved -->
		<xsl:param name="_position" select="1"/>
		<xsl:variable name="c" select="substring($text, 1, 1)"/>
		<xsl:choose>
			<xsl:when test="string-length($c) = 1">
				<xsl:variable name="cv">
					<xsl:call-template name="str.isBlankChar">
						<xsl:with-param name="char" select="$c"/>
					</xsl:call-template>
				</xsl:variable>
				<xsl:variable name="others">
					<xsl:call-template name="str.isBlank">
						<xsl:with-param name="text" select="substring($text, 2)"/>
					</xsl:call-template>
				</xsl:variable>
				<xsl:value-of select="$cv + $others"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="0"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Indicates if a characted is a blank character -->
	<xsl:template name="str.isBlankChar">
		<!-- Characted to test -->
		<xsl:param name="char" select="substring(., 1, 1)"/>
		<xsl:param name="_position" select="1"/>
		<xsl:variable name="c" select="substring($char, 1, 1)"/>
		<xsl:choose>
			<xsl:when test="contains($str.blanks, $c)">
				<xsl:value-of select="1"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="0"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Get the substring before the last occurrence of the given delimiter -->
	<xsl:template name="str.substringBeforeLast">
		<xsl:param name="text"/>
		<xsl:param name="delimiter"/>
		<xsl:if test="contains($text, $delimiter)">
			<xsl:variable name="b" select="substring-before($text, $delimiter)"/>
			<xsl:variable name="a" select="substring-after($text, $delimiter)"/>
			<xsl:value-of select="$b"/>
			<xsl:variable name="next">
				<xsl:call-template name="str.substringBeforeLast">
					<xsl:with-param name="text" select="$a"/>
					<xsl:with-param name="delimiter" select="$delimiter"/>
				</xsl:call-template>
			</xsl:variable>
			<xsl:if test="string-length($next) &gt; 0">
				<xsl:value-of select="$delimiter"/>
				<xsl:value-of select="$next"/>
			</xsl:if>
		</xsl:if>
	</xsl:template>

	<!-- Get the substring after the last occurence of the given delimiter -->
	<xsl:template name="str.substringAfterLast">
		<xsl:param name="text"/>
		<xsl:param name="delimiter"/>
		<xsl:choose>
			<xsl:when test="contains($text, $delimiter)">
				<xsl:call-template name="str.substringAfterLast">
					<xsl:with-param name="text" select="substring-after($text, $delimiter)"/>
					<xsl:with-param name="delimiter" select="$delimiter"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Number of occurrence of a given string at the beginning of another string -->
	<xsl:template name="str.startsWithCount">
		<!-- Text -->
		<xsl:param name="text"/>
		<!-- Substring to search -->
		<xsl:param name="needle"/>
		<!-- Internal use -->
		<xsl:param name="offset" select="1"/>
		<!-- Internal use -->
		<xsl:param name="count" select="0"/>
		<xsl:variable name="needleLength" select="string-length($needle)"/>
		<xsl:variable name="subtext" select="substring($text, $offset)"/>
		<xsl:choose>
			<xsl:when test="$needleLength = 0">
				<xsl:value-of select="$count"/>
			</xsl:when>
			<xsl:when test="starts-with($subtext, $needle)">
				<xsl:call-template name="str.startsWithCount">
					<xsl:with-param name="text" select="$text"/>
					<xsl:with-param name="needle" select="$needle"/>
					<xsl:with-param name="offset" select="$offset + $needleLength"/>
					<xsl:with-param name="count" select="$count + 1"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$count"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="str.endsWith">
		<!-- Text -->
		<xsl:param name="text"/>
		<!-- Substring to search -->
		<xsl:param name="needle"/>

		<xsl:variable name="needleLength" select="string-length($needle)"/>
		<xsl:variable name="textLength" select="string-length($text)"/>
		<xsl:variable name="end" select="substring($text, (($textLength - $needleLength) + 1))"/>

		<xsl:value-of select="($end = $needle)"/>
	</xsl:template>

	<!-- Number of occurrence of a given string at the end of another string -->
	<xsl:template name="str.endsWithCount">
		<!-- Text -->
		<xsl:param name="text"/>
		<!-- Substring to search -->
		<xsl:param name="needle"/>
		<!-- Internal use -->
		<xsl:param name="offset" select="0"/>
		<!-- Internal use -->
		<xsl:param name="count" select="0"/>
		<xsl:variable name="needleLength" select="string-length($needle)"/>
		<xsl:variable name="subtext" select="substring($text, 1, string-length($text) - $offset)"/>
		<xsl:variable name="subtextLength" select="string-length($subtext)"/>
		<xsl:variable name="toTest" select="substring($subtext, (($subtextLength - $needleLength) + 1))"/>
		<xsl:choose>
			<xsl:when test="($needleLength = 0) or ($subtextLength &lt; $needleLength)">
				<xsl:value-of select="$count"/>
			</xsl:when>
			<xsl:when test="$toTest = $needle">
				<xsl:call-template name="str.endsWithCount">
					<xsl:with-param name="text" select="$text"/>
					<xsl:with-param name="needle" select="$needle"/>
					<xsl:with-param name="offset" select="$offset + $needleLength"/>
					<xsl:with-param name="count" select="$count + 1"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$count"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Replace a string by another -->
	<xsl:template name="str.replaceAll">
		<!-- Text to process -->
		<xsl:param name="text"/>
		<!-- Text to replace -->
		<xsl:param name="replace"/>
		<!-- Replacement string -->
		<xsl:param name="by"/>
		<xsl:choose>
			<xsl:when test="contains($text, $replace)">
				<xsl:value-of select="substring-before($text,$replace)"/>
				<xsl:value-of select="$by"/>
				<xsl:call-template name="str.replaceAll">
					<xsl:with-param name="text" select="substring-after($text,$replace)"/>
					<xsl:with-param name="replace" select="$replace"/>
					<xsl:with-param name="by" select="$by"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Count occurences of a string in another -->
	<xsl:template name="str.count">
		<!-- Text to process -->
		<xsl:param name="text"/>
		<!-- Substring to count -->
		<xsl:param name="substring"/>
		<!-- Reserved -->
		<xsl:param name="_count" select="0"/>
		<xsl:choose>
			<xsl:when test="contains($text, $substring)">
				<xsl:call-template name="str.count">
					<xsl:with-param name="text" select="substring-after($text, $substring)"/>
					<xsl:with-param name="substring" select="$substring"/>
					<xsl:with-param name="_count" select="$_count + 1"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$_count"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Repeat a string -->
	<xsl:template name="str.repeat">
		<!-- Text to repeat -->
		<xsl:param name="text" select="' '"/>
		<!-- Repetitions -->
		<xsl:param name="iterations" select="1"/>
		<xsl:if test="$iterations &gt; 0">
			<xsl:value-of select="$text"/>
			<xsl:call-template name="str.repeat">
				<xsl:with-param name="iterations" select="$iterations - 1"/>
				<xsl:with-param name="text" select="$text"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<xsl:template name="str.elementLocalPart">
		<xsl:param name="node" select="."/>
		<xsl:value-of select="substring-after(name($node), ':' )"/>
	</xsl:template>

	<!-- Find the last breakable character index -->
	<xsl:template name="str.lastBreakableCharacterPosition">
		<!-- Text to process -->
		<xsl:param name="text" select="."/>
		<!-- Internal use -->
		<xsl:param name="_position" select="1"/>
		<!-- Internal use -->
		<xsl:param name="_bestValue" select="-1"/>
		<!-- the first 2 characters are always breakable,
			the others requires a space after -->
		<xsl:variable name="breakables" select="' &#9;,;:?.!'"/>
		<xsl:variable name="c">
			<xsl:value-of select="substring($breakables, $_position, 1)"/>
			<xsl:if test="$_position &gt; 2">
				<xsl:value-of select="' '"/>
			</xsl:if>
		</xsl:variable>
		<xsl:choose>
			<xsl:when test="(string-length($text) &gt; 0) and (string-length($c) = 1)">
				<xsl:choose>
					<xsl:when test="contains($text, $c)">
						<xsl:variable name="sub">
							<xsl:call-template name="str.substringBeforeLast">
								<xsl:with-param name="text" select="$text"/>
								<xsl:with-param name="delimiter" select="$c"/>
							</xsl:call-template>
						</xsl:variable>
						<xsl:variable name="subIndex" select="string-length($sub)"/>
						<xsl:variable name="newBest">
							<xsl:choose>
								<xsl:when test="$subIndex &gt; $_bestValue">
									<xsl:value-of select="$subIndex"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="$_bestValue"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:variable>
						<xsl:call-template name="str.lastBreakableCharacterPosition">
							<xsl:with-param name="text" select="$text"/>
							<xsl:with-param name="_position" select="$_position + 1"/>
							<xsl:with-param name="_bestValue" select="$newBest"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="str.lastBreakableCharacterPosition">
							<xsl:with-param name="text" select="$text"/>
							<xsl:with-param name="_position" select="$_position + 1"/>
							<xsl:with-param name="_bestValue" select="$_bestValue"/>
						</xsl:call-template>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="$_bestValue &gt;= 0">
						<xsl:value-of select="$_bestValue + 1"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="string-length($text) + 1"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Wrap text -->
	<xsl:template name="str.wrap">
		<!-- Text to wrap -->
		<xsl:param name="text" select="."/>
		<!-- Maximum number of character per line -->
		<xsl:param name="lineMaxLength" select="80"/>
		<!-- End-of-line string -->
		<xsl:param name="endlChar" select="$str.endl"/>

		<xsl:variable name="hasEndl" select="contains($text, $endlChar)"/>

		<!-- Get a line -->
		<xsl:variable name="item">
			<xsl:choose>
				<xsl:when test="$hasEndl">
					<xsl:value-of select="substring-before($text, $endlChar)"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$text"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<!-- Get left blanks -->
		<xsl:variable name="leftTrimmedItem">
			<xsl:call-template name="str.trimLeft">
				<xsl:with-param name="text" select="$item"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:variable name="leftBlankCount" select="(string-length($item) - string-length($leftTrimmedItem))"/>
		<xsl:variable name="remainingPartPadding" select="substring($item, 1, $leftBlankCount)"/>

		<xsl:if test="string-length($item) &gt; 0">
			<!-- <text>(process: </text><value-of select="$item" /><text> of </text><value-of select="$text" /><text>)</text> -->
			<xsl:choose>
				<xsl:when test="string-length($item) &gt; $lineMaxLength">
					<!-- Text chunk before line length limit -->
					<xsl:variable name="splititem" select="substring($item, 1, $lineMaxLength)"/>
					<!-- Position of the last breakable char in text chunk -->
					<xsl:variable name="breakPosition">
						<xsl:call-template name="str.lastBreakableCharacterPosition">
							<xsl:with-param name="text" select="$splititem"/>
						</xsl:call-template>
					</xsl:variable>
					<xsl:variable name="b" select="substring($splititem, $breakPosition, 1)"/>
					<xsl:variable name="isBlank">
						<xsl:choose>
							<xsl:when test="string-length($b) &gt; 0">
								<xsl:call-template name="str.isBlankChar">
									<xsl:with-param name="char" select="$b"/>
								</xsl:call-template>
							</xsl:when>
						</xsl:choose>
					</xsl:variable>
					<xsl:variable name="len">
						<xsl:choose>
							<xsl:when test="$breakPosition &gt; $lineMaxLength">
								<xsl:value-of select="$lineMaxLength"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="$breakPosition"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<!-- Text chunk before breakable -->
					<xsl:variable name="part">
						<xsl:choose>
							<xsl:when test="($breakPosition = 1) and ($isBlank &gt; 0)">
								<xsl:value-of select="substring($item, 2, $len - 1)"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="substring($item, 1, $len)"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<xsl:if test="string-length($part) &gt; 0">
						<xsl:value-of select="$part"/>
						<xsl:value-of select="$endlChar"/>
					</xsl:if>
					<xsl:variable name="remaining" select="concat($remainingPartPadding, substring($item, $len + 1))"/>
					<!-- remaining part -->
					<xsl:if test="string-length($remaining) &gt; 0">
						<!-- <text>(remain: </text><value-of select="$remaining" /><text>)</text> -->
						<xsl:call-template name="str.wrap">
							<xsl:with-param name="text" select="$remaining"/>
							<xsl:with-param name="lineMaxLength" select="$lineMaxLength"/>
							<xsl:with-param name="endlChar" select="$endlChar"/>
						</xsl:call-template>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$item"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
		<!-- other lines -->
		<xsl:if test="$hasEndl = true()">
			<xsl:value-of select="$endlChar"/>
			<xsl:variable name="otherLines" select="substring-after($text, $endlChar)"/>
			<xsl:if test="string-length($otherLines) &gt; 0">
				<!-- <text>(continue with: </text><value-of select="$otherLines" /><text>)</text> -->
				<xsl:call-template name="str.wrap">
					<xsl:with-param name="text" select="$otherLines"/>
					<xsl:with-param name="lineMaxLength" select="$lineMaxLength"/>
					<xsl:with-param name="endlChar" select="$endlChar"/>
				</xsl:call-template>
			</xsl:if>
		</xsl:if>
	</xsl:template>

	<!-- Indent a text block by adding indentaction characters at the beginning of all text lines -->
	<xsl:template name="str.prependLine">
		<xsl:param name="text"/>
		<xsl:param name="level" select="1"/>
		<xsl:param name="prependedText">
			<xsl:text>	</xsl:text>
		</xsl:param>
		<xsl:param name="endlChar" select="$str.endl"/>
		<xsl:param name="wrap" select="false()"/>
		<xsl:param name="lineMaxLength" select="80"/>
		<xsl:variable name="fullPrependedText">
			<xsl:call-template name="str.repeat">
				<xsl:with-param name="iterations" select="$level"/>
				<xsl:with-param name="text" select="$prependedText"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:variable name="realLineMaxLength" select="$lineMaxLength - string-length($fullPrependedText)"/>
		<xsl:variable name="content">
			<xsl:choose>
				<xsl:when test="$wrap">
					<xsl:call-template name="str.wrap">
						<xsl:with-param name="text" select="$text"/>
						<xsl:with-param name="endlChar" select="$endlChar"/>
						<xsl:with-param name="lineMaxLength" select="$realLineMaxLength"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$text"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:value-of select="$fullPrependedText"/>
		<xsl:call-template name="str.replaceAll">
			<xsl:with-param name="text">
				<xsl:value-of select="$content"/>
			</xsl:with-param>
			<xsl:with-param name="replace">
				<xsl:value-of select="$endlChar"/>
			</xsl:with-param>
			<xsl:with-param name="by">
				<xsl:value-of select="$endlChar"/>
				<xsl:value-of select="$fullPrependedText"/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<!-- Trim text at the beginning and the end -->
	<xsl:template name="str.trim">
		<!-- Text to trim -->
		<xsl:param name="text" select="."/>
		<xsl:variable name="lTrimmed">
			<xsl:call-template name="str.trimLeft">
				<xsl:with-param name="text" select="$text"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:call-template name="str.trimRight">
			<xsl:with-param name="text" select="$lTrimmed"/>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="str.trimLeft">
		<xsl:param name="text"/>
		<xsl:choose>
			<xsl:when test="starts-with($text,'&#9;') or starts-with($text,'&#10;') or starts-with($text,'&#13;') or starts-with($text,' ')">
				<xsl:call-template name="str.trimLeft">
					<xsl:with-param name="text" select="substring($text, 2)"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="str.trimRight">
		<xsl:param name="text"/>
		<xsl:variable name="last-char">
			<xsl:value-of select="substring($text, string-length($text), 1)"/>
		</xsl:variable>
		<xsl:choose>
			<xsl:when test="($last-char = '&#9;') or ($last-char = '&#10;') or ($last-char = '&#13;') or ($last-char = ' ')">
				<xsl:call-template name="str.trimRight">
					<xsl:with-param name="text" select="substring($text, 1, string-length($text) - 1)"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:variable name="str.smallCase" select="'abcdefghijklmnopqrstuvwxyz'"/>
	<xsl:variable name="str.upperCase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
	<xsl:template name="str.toUpper">
		<xsl:param name="text" select="."/>
		<xsl:value-of select="translate($text, $str.smallCase, $str.upperCase)"/>
	</xsl:template>

	<xsl:template name="str.toLower">
		<xsl:param name="text" select="."/>
		<xsl:value-of select="translate($text, $str.upperCase, $str.smallCase)"/>
	</xsl:template>

	<xsl:variable name="str.ascii">
		<xsl:text> !"#$%&amp;'()*+,-./0123456789:;&lt;=&gt;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~</xsl:text>
	</xsl:variable>
	<xsl:variable name="str.hex">
		<xsl:text>0123456789ABCDEF</xsl:text>
	</xsl:variable>
	<!-- http://lists.xml.org/archives/xml-dev/200109/msg00248.html -->
	<xsl:template name="str.asciiToHex">
		<xsl:param name="text"/>
		<xsl:param name="prefix"/>
		<xsl:param name="suffix"/>
		<xsl:if test="$text">
			<xsl:variable name="firstChar" select="substring($text, 1, 1)"/>
			<xsl:variable name="asciiValue" select="string-length(substring-before($str.ascii, $firstChar)) + 32"/>
			<xsl:variable name="hexDigit1" select="substring($str.hex, floor($asciiValue div 16) + 1, 1)"/>
			<xsl:variable name="hexDigit2" select="substring($str.hex, $asciiValue mod 16 + 1, 1)"/>
			<xsl:if test="$prefix">
				<xsl:value-of select="$prefix"/>
			</xsl:if>
			<xsl:value-of select="concat($hexDigit1, $hexDigit2)"/>
			<xsl:if test="$suffix">
				<xsl:value-of select="$suffix"/>
			</xsl:if>
			<xsl:if test="string-length($text) &gt; 1">
				<xsl:call-template name="str.asciiToHex">
					<xsl:with-param name="text" select="substring($text, 2)"/>
					<xsl:with-param name="prefix" select="$prefix"/>
					<xsl:with-param name="suffix" select="$suffix"/>
				</xsl:call-template>
			</xsl:if>
		</xsl:if>
	</xsl:template>


	
	<xsl:output method="text" indent="yes" encoding="utf-8"/>

	<!-- Should be overriden by Datasource implementations -->
	<xsl:template name="sql.protectString">
		<xsl:param name="string"/>
		<xsl:value-of select="$string"/>
	</xsl:template>

	<!-- Should be overriden by Datasource implementations -->
	<xsl:template name="sql.elementName">
		<xsl:param name="name" select="@name"/>
		<xsl:text>"</xsl:text>
		<xsl:value-of select="normalize-space($name)"/>
		<xsl:text>"</xsl:text>
	</xsl:template>

	<xsl:template name="sql.elementNameList">
		<xsl:call-template name="sql.elementName"/>
		<xsl:if test="position() != last()">
			<xsl:text>, </xsl:text>
		</xsl:if>
	</xsl:template>

	<xsl:template name="sql.indexedElementName">
		<xsl:param name="name" select="@name"/>
		<xsl:text>"</xsl:text>
		<xsl:value-of select="normalize-space($name)"/>
		<xsl:text>"</xsl:text>
		<xsl:if test="@collation">
			<xsl:text> COLLATE </xsl:text>
			<xsl:value-of select="@collation"/>
		</xsl:if>
		<xsl:if test="@order">
			<xsl:text>
			</xsl:text>
			<xsl:value-of select="@order"/>
		</xsl:if>
	</xsl:template>

	<xsl:template name="sql.indexedElementList">
		<xsl:call-template name="sql.indexedElementName"/>
		<xsl:if test="position() != last()">
			<xsl:text>, </xsl:text>
		</xsl:if>
	</xsl:template>

	<!-- Convert generic data types into Datasource type -->
	<!-- Should be overriden by Datasource implementations -->
	<xsl:template name="sql.dataTypeTranslation">
		<xsl:param name="typeAffinity"/>
		<xsl:value-of select="normalize-space($typeAffinity)"/>
	</xsl:template>

	<xsl:template name="sql.eventActionTranslation">
		<xsl:param name="action"/>
		<xsl:choose>
			<xsl:when test="$action = 'null'">
				<xsl:text>SET null</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'default'">
				<xsl:text>SET DEFAULT</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'cascade'">
				<xsl:text>CASCADE</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'restrict'">
				<xsl:text>RESTRICT</xsl:text>
			</xsl:when>
			<xsl:when test="$action = 'noaction'">
				<xsl:text>NO ACTION</xsl:text>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- Display the name of an existing database table -->
	<!-- Both id or name attribute could be used to reference the table -->
	<!-- id is required to display the full name of the table (db.table) -->
	<xsl:template name="sql.tableReferenceName">
		<xsl:param name="fullName" select="false()"/>
		<xsl:param name="name" select="@name"/>
		<xsl:param name="id" select="@id"/>

		<xsl:choose>
			<xsl:when test="$name">
				<xsl:call-template name="sql.elementName">
					<xsl:with-param name="name" select="normalize-space($name)"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="$id">
				<xsl:if test="$fullName">
					<xsl:if test="//sql:table[@id=$id]/../@name">
						<xsl:call-template name="sql.elementName">
							<xsl:with-param name="name">
								<xsl:value-of select="//sql:table[@id=$id]/../@name"/>
							</xsl:with-param>
						</xsl:call-template>
						<xsl:text>.</xsl:text>
					</xsl:if>
				</xsl:if>
				<xsl:call-template name="sql.elementName">
					<xsl:with-param name="name">
						<xsl:value-of select="//sql:table[@id=$id]/@name"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!-- /////////////////////////////////////////////////////////////////////// -->
	<xsl:template match="sql:Datasource">
		<xsl:text>-- ns-xml database structure to SQL translation</xsl:text>
		<xsl:value-of select="$str.endl"/>
		<xsl:text>-- Structure version: </xsl:text>
		<xsl:value-of select="@version"/>
		<xsl:value-of select="$str.endl"/>
		<xsl:if test="@author">
			<xsl:text>-- Author: </xsl:text>
			<xsl:value-of select="@author"/>
			<xsl:value-of select="$str.endl"/>
		</xsl:if>
		<xsl:apply-templates select="sql:database"/>
	</xsl:template>

	<xsl:template match="sql:database">
		<xsl:text>-- Database </xsl:text>
		<xsl:value-of select="@name"/>
		<xsl:value-of select="$str.endl"/>
		<xsl:apply-templates select="sql:table|sql:index"/>
	</xsl:template>

	<xsl:template match="sql:database/sql:index">
		<xsl:text>CREATE </xsl:text>
		<xsl:if test="@unique">
			<xsl:text>UNIQUE </xsl:text>
		</xsl:if>
		<xsl:text>INDEX </xsl:text>
		<!-- @todo IF NOT EXISTS -->
		<xsl:if test="../@name">
			<xsl:call-template name="sql.elementName">
				<xsl:with-param name="name" select="../@name"/>
			</xsl:call-template>
			<xsl:text>.</xsl:text>
		</xsl:if>
		<xsl:call-template name="sql.elementName"/>
		<xsl:text> ON </xsl:text>
		<xsl:apply-templates/>
		<!-- <call-template name="sql.tableReferenceName"> <with-param name="fullName" select="true()"/> <with-param name="name" select=""></with-param>
			</call-template> -->
		<xsl:text> (</xsl:text>
		<xsl:for-each select="sql:field">
			<xsl:call-template name="sql.elementNameList"/>
		</xsl:for-each>
		<xsl:text>);</xsl:text>
		<xsl:value-of select="$str.endl"/>
	</xsl:template>

	<xsl:template match="sql:table">
		<xsl:text>CREATE TABLE </xsl:text>
		<xsl:if test="../@name">
			<xsl:call-template name="sql.elementName">
				<xsl:with-param name="name">
					<xsl:value-of select="../@name"/>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:text>.</xsl:text>
		</xsl:if>
		<xsl:call-template name="sql.elementName"/>
		<xsl:value-of select="$str.endl"/>
		<xsl:text>(</xsl:text>
		<xsl:value-of select="$str.endl"/>

		<!-- List of fields -->
		<xsl:for-each select="sql:field">
			<xsl:apply-templates select="."/>
			<xsl:if test="position() != last()">
				<xsl:text>,</xsl:text>
				<xsl:value-of select="$str.endl"/>
			</xsl:if>
		</xsl:for-each>

		<!-- Primary key -->
		<xsl:if test="sql:primarykey">
			<xsl:text>,</xsl:text>
			<xsl:value-of select="$str.endl"/>
			<xsl:apply-templates select="sql:primarykey"/>
		</xsl:if>
		<!-- Foreign keys -->
		<xsl:if test="sql:foreignkey">
			<xsl:text>,</xsl:text>
			<xsl:for-each select="sql:foreignkey">
				<xsl:value-of select="$str.endl"/>
				<xsl:apply-templates select="."/>
				<xsl:if test="position() != last()">
					<xsl:text>, </xsl:text>
				</xsl:if>
			</xsl:for-each>
		</xsl:if>
		<xsl:value-of select="$str.endl"/>
		<xsl:text>);</xsl:text>
		<xsl:value-of select="$str.endl"/>
	</xsl:template>

	<xsl:template match="sql:table/sql:field">
		<xsl:call-template name="sql.elementName"/>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="sql:index/sql:tableref">
		<xsl:call-template name="sql.tableReferenceName">
			<xsl:with-param name="fullName" select="false()"/>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:tableref">
		<xsl:call-template name="sql.tableReferenceName">
			<xsl:with-param name="fullName" select="false()"/>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:reference">
		<xsl:text> REFERENCES </xsl:text>
		<xsl:apply-templates select="sql:tableref"/>
		<xsl:if test="sql:field">
			<xsl:text> (</xsl:text>
			<xsl:for-each select="sql:field">
				<xsl:call-template name="sql.elementNameList"/>
			</xsl:for-each>
			<xsl:text>)</xsl:text>
			<xsl:apply-templates select="sql:onupdate"/>
			<xsl:apply-templates select="sql:ondelete"/>
		</xsl:if>
	</xsl:template>

	<xsl:template match="sql:table/sql:primarykey">
		<xsl:if test="@name">
			<xsl:text>CONSTRAINT </xsl:text>
			<xsl:call-template name="sql.elementName"/>
			<xsl:text> </xsl:text>
		</xsl:if>
		<xsl:text>PRIMARY KEY (</xsl:text>
		<xsl:for-each select="sql:field">
			<xsl:call-template name="sql.indexedElementList"/>
		</xsl:for-each>
		<xsl:text>)</xsl:text>
	</xsl:template>

	<xsl:template match="sql:table/sql:foreignkey">
		<xsl:if test="@name">
			<xsl:text>CONSTRAINT </xsl:text>
			<xsl:call-template name="sql.elementName"/>
			<xsl:text>
			</xsl:text>
		</xsl:if>
		<xsl:text>FOREIGN KEY (</xsl:text>
		<xsl:for-each select="sql:field">
			<xsl:call-template name="sql.elementNameList"/>
		</xsl:for-each>
		<xsl:text>)</xsl:text>
		<xsl:apply-templates select="sql:reference"/>
	</xsl:template>

	<xsl:template match="sql:datatype">
		<xsl:variable name="name">
			<xsl:choose>
				<xsl:when test="@name">
					<xsl:value-of select="@name"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="sql.dataTypeTranslation">
						<xsl:with-param name="typeAffinity" select="sql:affinity"/>
					</xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:if test="string-length($name) &gt; 0">
			<xsl:text> </xsl:text>
			<xsl:value-of select="normalize-space($name)"/>
			<xsl:if test="sql:length">
				<xsl:text>(</xsl:text>
				<xsl:apply-templates select="sql:length"/>
				<xsl:if test="sql:decimal">
					<xsl:text>,</xsl:text>
					<xsl:apply-templates select="sql:decimal"/>
				</xsl:if>
				<xsl:text>)</xsl:text>
			</xsl:if>
		</xsl:if>
	</xsl:template>

	<xsl:template match="sql:size">
		<xsl:text>(</xsl:text>
		<xsl:value-of select="."/>
		<xsl:if test="@decimalsize">
			<xsl:text>,</xsl:text>
			<xsl:value-of select="@decimalsize"/>
		</xsl:if>
		<xsl:text>)</xsl:text>
	</xsl:template>

	<xsl:template match="sql:default">
		<xsl:text> DEFAULT </xsl:text>
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="sql:notnull">
		<xsl:text> NOT NULL</xsl:text>
	</xsl:template>

	<xsl:template match="sql:onupdate">
		<xsl:text> ON UPDATE </xsl:text>
		<xsl:call-template name="sql.eventActionTranslation">
			<xsl:with-param name="action">
				<xsl:value-of select="@action"/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:ondelete">
		<xsl:text> ON DELETE </xsl:text>
		<xsl:call-template name="sql.eventActionTranslation">
			<xsl:with-param name="action">
				<xsl:value-of select="@action"/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="sql:text">
		<xsl:call-template name="sql.protectString">
			<xsl:with-param name="string">
				<xsl:value-of select="normalize-strin(.)"/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>



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
		<xsl:param name="typeAffinity"/>
		<xsl:choose>
			<xsl:when test="$typeAffinity = 'boolean'">
				<xsl:text>NUMERIC</xsl:text>
			</xsl:when>
			<xsl:when test="$typeAffinity = 'integer'">
				<xsl:text>INTEGER</xsl:text>
			</xsl:when>
			<xsl:when test="$typeAffinity = 'decimal'">
				<xsl:text>REAL</xsl:text>
			</xsl:when>
			<xsl:when test="$typeAffinity = 'datetime'">
				<xsl:text>NUMERIC</xsl:text>
			</xsl:when>
			<xsl:when test="$typeAffinity = 'string'">
				<xsl:text>TEXT</xsl:text>
			</xsl:when>
			<xsl:when test="$typeAffinity = 'binary'">
				<xsl:text>BLOB</xsl:text>
			</xsl:when>
			<xsl:when test="$typeAffinity = 'bitset'">
				<xsl:text>INTEGER</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<!-- The default will be a string -->
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- /////////////////////////////////////////////////////////////// -->

	<xsl:template match="sql:table/sql:field">
		<xsl:call-template name="sql.elementName"/>
		<!-- @todo manage autoincrement + primary key etc. -->
		<xsl:apply-templates/>
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
