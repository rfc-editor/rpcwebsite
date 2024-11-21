<?xml version="1.0" encoding="UTF-8"?>
<!-- $Id: rfc_element_html_templates.xsl,v 2.5 2024/08/13 20:29:01 priyanka Exp $ -->
<xsl:stylesheet version="1.0"
                xmlns:rfc="https://www.rfc-editor.org/rfc-index"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<!--
These templates are imported into the main XSLT style sheets that create the
HTML output for the rfc-index.html and rfc-index2.html files. The latter sorts
descending.
-->
  <xsl:template match="rfc:rfc-entry">
    <tr valign="top">
      <td valign="top">
        <script type="text/javascript">
          doMainDocLink('<xsl:value-of select="rfc:doc-id"/>');
        </script>
        <noscript>
          <xsl:value-of select="substring(rfc:doc-id,4)"/>
        </noscript>
      </td>
      <td>
        <b>
          <xsl:value-of select="rfc:title"/>
        </b>
        <xsl:text> </xsl:text>
        <xsl:variable name="authors" select="count(rfc:author)" />
        <xsl:for-each select="rfc:author">
          <xsl:value-of select="rfc:name"/>
          <xsl:if test="position() &lt; $authors">
            <xsl:text>,</xsl:text>
          </xsl:if>
          <xsl:text> </xsl:text>
        </xsl:for-each>
        <xsl:choose>
          <xsl:when test="rfc:date/rfc:day = '1'">
            [ <xsl:value-of select="rfc:date/rfc:day"/><xsl:text> </xsl:text><xsl:value-of select="rfc:date/rfc:month"/><xsl:text> </xsl:text><xsl:value-of select="rfc:date/rfc:year"/> ]
          </xsl:when>
          <xsl:otherwise>
            [ <xsl:value-of select="rfc:date/rfc:month"/><xsl:text> </xsl:text><xsl:value-of select="rfc:date/rfc:year"/> ]
          </xsl:otherwise>
        </xsl:choose>
       (<xsl:if test="count(rfc:format) != 0">
          <xsl:apply-templates select="rfc:format"/>
        </xsl:if>)
        <xsl:if test="count(rfc:obsoletes) != 0">
          <xsl:apply-templates select="rfc:obsoletes"/>
        </xsl:if>
        <xsl:if test="count(rfc:obsoleted-by) != 0">
          <xsl:apply-templates select="rfc:obsoleted-by"/>
        </xsl:if>
        <xsl:if test="count(rfc:updates) != 0">
          <xsl:apply-templates select="rfc:updates"/>
        </xsl:if>
        <xsl:if test="count(rfc:updated-by) != 0">
          <xsl:apply-templates select="rfc:updated-by"/>
        </xsl:if>
        <xsl:if test="count(rfc:is-also) != 0">
          <xsl:apply-templates select="rfc:is-also"/>
        </xsl:if>
<!--
        <xsl:if test="count(rfc:see-also) != 0">
          <xsl:apply-templates select="rfc:see-also"/>
        </xsl:if>
-->
        (Status: <xsl:value-of select="rfc:current-status"/>)
        <xsl:if test="count(rfc:stream) != 0">
          (Stream: <xsl:value-of select="rfc:stream"/>
          <xsl:if test="count(rfc:area) != 0">
            <xsl:text>, Area: </xsl:text>
            <xsl:value-of select="rfc:area"/>
          </xsl:if>
          
          <xsl:if test="count(rfc:wg_acronym) != 0">
            <xsl:text>, WG: </xsl:text>
            <xsl:value-of select="rfc:wg_acronym"/>
          </xsl:if>)
        </xsl:if>
        <xsl:if test="count(rfc:doi) != 0">
           (DOI: <xsl:value-of select="rfc:doi"/>)
        </xsl:if>

      </td>
    </tr>
  </xsl:template>


  <xsl:template match="rfc:obsoletes">
    (Obsoletes
    <xsl:for-each select="rfc:doc-id">
      <script type="text/javascript">
        doStandardDocLink('<xsl:value-of select="."/>');
      </script>
      <noscript>
        <xsl:value-of select="."/>
      </noscript>
      <xsl:if test="position() &lt; last()">
        <xsl:text>,</xsl:text>
      </xsl:if>
      <xsl:if test="position() != last()">
        <xsl:text> </xsl:text>
      </xsl:if>
    </xsl:for-each>)
  </xsl:template>

  <xsl:template match="rfc:updates">
    (Updates
    <xsl:for-each select="rfc:doc-id">
      <script type="text/javascript">
        doStandardDocLink('<xsl:value-of select="."/>');
      </script>
      <noscript>
        <xsl:value-of select="."/>
      </noscript>
      <xsl:if test="position() &lt; last()">
        <xsl:text>,</xsl:text>
      </xsl:if>
      <xsl:if test="position() != last()">
        <xsl:text> </xsl:text>
      </xsl:if>
    </xsl:for-each>)
  </xsl:template>

  <xsl:template match="rfc:updated-by">
    (Updated-By
    <xsl:variable name="obs_count" select="count(rfc:doc-id)"/>
    <xsl:for-each select="rfc:doc-id">
      <script type="text/javascript">
        doStandardDocLink('<xsl:value-of select="."/>');
      </script>
      <noscript>
        <xsl:value-of select="."/>
      </noscript>
      <xsl:if test="position() &lt; $obs_count">
        <xsl:text>,</xsl:text>
      </xsl:if>
      <xsl:if test="position() != $obs_count">
        <xsl:text> </xsl:text>
      </xsl:if>
    </xsl:for-each>)
  </xsl:template>

  <xsl:template match="rfc:obsoleted-by">
    (Obsoleted-By
    <xsl:variable name="obs_count" select="count(rfc:doc-id)"/>
    <xsl:for-each select="rfc:doc-id">
      <script type="text/javascript">
        doStandardDocLink('<xsl:value-of select="."/>');
      </script>
      <noscript>
        <xsl:value-of select="."/>
      </noscript>
      <xsl:if test="position() &lt; $obs_count">
        <xsl:text>,</xsl:text>
      </xsl:if>
      <xsl:if test="position() != $obs_count">
        <xsl:text> </xsl:text>
      </xsl:if>
    </xsl:for-each>)
  </xsl:template>

  <xsl:template match="rfc:is-also">
    (Also
    <xsl:variable name="ia_count" select="count(rfc:doc-id)"/>
    <xsl:for-each select="rfc:doc-id">
      <script type="text/javascript">
        doStandardDocLink('<xsl:value-of select="."/>');
      </script>
      <noscript>
        <xsl:value-of select="."/>
      </noscript>
      <xsl:if test="position() &lt; $ia_count">
        <xsl:text>,</xsl:text>
      </xsl:if>
      <xsl:if test="position() != $ia_count">
        <xsl:text> </xsl:text>
      </xsl:if>
    </xsl:for-each>)
  </xsl:template>

  <xsl:template match="rfc:see-also">
    (See Also
    <xsl:variable name="sa_count" select="count(rfc:doc-id)"/>
    <xsl:for-each select="rfc:doc-id">
      <script type="text/javascript">
        doStandardDocLink('<xsl:value-of select="."/>');
      </script>
      <noscript>
        <xsl:value-of select="."/>
      </noscript>
      <xsl:if test="position() &lt; $sa_count">
        <xsl:text>,</xsl:text>
      </xsl:if>
      <xsl:if test="position() != $sa_count">
        <xsl:text> </xsl:text>
      </xsl:if>
    </xsl:for-each>)
  </xsl:template>


  <xsl:template match="rfc:format">
    <xsl:variable name="format_count" select="count(rfc:file-format)"/>
    <xsl:for-each select="rfc:file-format">
        <xsl:choose>
          <xsl:when test=". = 'ASCII'">TXT</xsl:when>
          <xsl:otherwise>
             <xsl:value-of select="."/>
          </xsl:otherwise>  
        </xsl:choose>
        <xsl:if test="position() &lt; $format_count">
          <xsl:text>, </xsl:text>
        </xsl:if>
        <xsl:if test="position() != $format_count">
          <xsl:text></xsl:text>
        </xsl:if>
    </xsl:for-each>
  </xsl:template> 
  
  <xsl:template match="rfc:rfc-not-issued-entry">
    <tr valign="top">
      <td valign="top">
        <xsl:value-of select="substring(rfc:doc-id,4)"/>
      </td>
      <td>
        Not Issued
      </td>
    </tr>
  </xsl:template>
</xsl:stylesheet>

