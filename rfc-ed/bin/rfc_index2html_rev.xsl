<?xml version="1.0" encoding="UTF-8"?>
<!-- $Id: rfc_index2html_rev.xsl,v 2.10 2024/08/13 20:29:01 priyanka Exp $ -->
<xsl:stylesheet version="1.0"
                xmlns:rfc="https://www.rfc-editor.org/rfc-index"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<!-- Import the element templates to generate HTML elements for RFC documents -->
  <xsl:import href="rfc_element_html_templates.xsl"/>
  <xsl:output method="html" indent="yes" version="4.0"/>
<!-- 
This template drives the processing. RFC documents are sorted in descending
order.
-->
  <xsl:template match="rfc:rfc-index">
  <html>
    <head>
      <title>RFC INDEX</title>
      <script src="scripts/rfcxml.js" type="text/javascript">
      </script>
    </head>
    <body>
      <h1 style="text-align: center; font-weight: bold; text-decoration:
      underline; color: brown">RFC Index</h1>
      <script type="text/javascript">
        showGeneratedDate();
      </script>
      <noscript>
        <p style="color:red; font-variant: small-caps; font-weight: bold">
          Page requires <em>JavaScript</em> enabled for proper functioning.</p>
      </noscript>
      <p>
        This file contains citations for all RFCs in reverse numeric order. RFC citations appear in this format:
      </p>
      <table border="5">
        <tr>
          <td> Num </td>
          <td> Information </td></tr>
        <tr>
          <td> #### </td>
          <td><b>Title of RFC</b> Author list [ Month Year ] (Format) (Obsoletes xxxx)
          (Obsoleted-By xxxx) (Updates xxxx) (Updated-By xxxx) (Also zzz##) (Status:
          status) (Stream: stream) (DOI: doi)<br/><br/> or  ####  Not Issued. 
          </td>
        </tr>
      </table>
      <p>For example:</p>
      <table border="5">
        <tr><td> Num </td><td> Information </td></tr>
        <tr><td><a href="https://www.rfc-editor.org/rfc/rfc5234.txt">5234</a></td><td>
       <b>Augmented BNF for Syntax Specifications: ABNF</b> D. Crocker, P. Overell
       [ January 2008 ]
       (TXT, HTML)

   (Obsoletes
       <script type="text/javascript">
       doStandardDocLink('RFC4234');
       </script><noscript>RFC4234</noscript>)

   (Also
       <script type="text/javascript">
       doStandardDocLink('STD0068');
       </script><noscript>STD0068</noscript>)

       (Status: INTERNET STANDARD)

         (Stream: IETF, WG: NON WORKING GROUP)
	  (DOI: 10.17487/RFC5234)
</td></tr></table> 
      <h2>Key to Citations</h2>
      <ul>
        <li>#### is the RFC number.</li>
        <li>Following the number are the title, the author list, and the
        publication date.</li>
        <li>The format follows in parentheses. One or more of the following 
          formats are listed:  text (TXT), PostScript (PS), Portable Document 
          Format (PDF), HTML, XML.</li>
        <li>"Obsoletes xxxx" refers to other RFCs that this one replaces.</li>
        <li>"Obsoleted-By xxxx" refers to RFCs that have replaced this one.</li>
        <li>"Updates xxxx" refers to other RFCs that this one merely updates
        but does not replace.</li>
        <li>"Updated by xxxx" refers to RFCs that have
          updated but not replaced this one.</li>
        <li>"(Also zzz##)" gives pointer(s) to equivalent sub-series
          documents, if any. Here zzz is one of the sub-series designators STD,
          BCP, or FYI.  In a few cases, the Also field points to an equivalent
          number in some other document series.</li>

        <li>The Status field gives the document's current status (see <a href="https://www.rfc-editor.org/rfc/rfc2026.txt">RFC 2026</a> and <a href="https://www.rfc-editor.org/rfc/rfc6410.txt">RFC 6410</a>).</li>
        <li>The Stream field gives the document's stream (see <a href="https://www.rfc-editor.org/rfc/rfc4844.txt">RFC 4844</a>), followed by Area and WG when relevant.</li>
        <li>The DOI field gives the Digital Object Identifier.</li>
      </ul>
      <p>See the <a href="https://www.rfc-editor.org/">RFC Editor Web page</a> for more
      information.</p>
      <h1 style="text-align: center; font-weight: bold; text-decoration: underline">RFC Index</h1>
      <table>
        <tr bgcolor="#ffddbb"><td> Num </td><td>Information</td></tr>
<!-- Select the subset of elements that cover RFC documents, both published
     and not issued. This ignores BCP, FYI, and STD entry elements. -->
        <xsl:for-each select="rfc:rfc-entry | rfc:rfc-not-issued-entry">
<!-- Sort the subset before applying templates. -->
          <xsl:sort select="rfc:doc-id" order="descending"/>
          <xsl:apply-templates select="."/>
        </xsl:for-each>
      </table>
    </body>
    </html>
  </xsl:template>

</xsl:stylesheet>
