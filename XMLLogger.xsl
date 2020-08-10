<?xml version="1.0" encoding="UTF-8"?>
<html xsl:version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<head>
<style>
.EMERGENCY
{
	color: white;
	background-color: darkred;
	font-weight: bold;
}
.ALERT
{
	color: white;
	background-color: firebrick;
	font-weight: bold;
}
.CRITICAL
{
	color: white;
	background-color: red;
}
.ERROR
{
	color: black;
	background-color: salmon;
}
.WARNING
{
	color: black;
	background-color: gold;
}
.NOTICE
{
	color: black;
	background-color: white;
}
.INFO
{
	color: darkblue;
	background-color: aliceblue;
}
.DEBUG
{
	color: darkblue;
	background-color: lightblue;
	font-style: italic;
}
.caller
{
	font-size: 8pt;
	font-weight: normal;
}
.ua
{
	font-style: italic;
	font-size: 7pt;
	font-weight: normal;
}
</style>
</head>
<body style="font-family:Arial;font-size:8pt;background-color:#EEEEEE">
<table width="100%"><tbody>
<xsl:for-each select="log/item">
	<xsl:variable name="rowclass"><xsl:value-of select="level"/></xsl:variable>
    <tr class="{$rowclass}">
		<td><xsl:value-of select="timestamp"/></td>
		<td><xsl:value-of select="user"/></td>
		<td class="caller"><xsl:value-of select="caller"/></td>
		<td class="ua"><xsl:value-of select="useragent"/></td>
	</tr>
    <tr class="{level}">
		<td><xsl:value-of select="$rowclass"/></td>
		<td colspan="3"><xsl:value-of select="message"/></td>
    </tr>
	<xsl:for-each select=".//context">
		<tr class="{$rowclass}">
			<td></td>
			<td><xsl:value-of select="key"/></td>
			<td colspan="2"><xsl:value-of select="value"/></td>
		</tr>
	</xsl:for-each>
	<xsl:if test="exception">
		<tr class="{$rowclass}">
			<td></td>
			<td colspan="3"><xsl:value-of select="exception/class"/> trace:</td>
		</tr>
	</xsl:if>
	<xsl:for-each select=".//exception/trace">
		<tr class="{$rowclass}">
			<td></td>
			<td><xsl:value-of select="class"/></td>
			<td><xsl:value-of select="function"/></td>
			<td><xsl:value-of select="file"/> (<xsl:value-of select="line"/>)</td>
		</tr>
	</xsl:for-each>
</xsl:for-each>
</tbody></table>
</body>
</html> 