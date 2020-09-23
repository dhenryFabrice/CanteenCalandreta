<?xml version="1.0" encoding="ISO-8859-1" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
  <html xmlns="http://www.w3.org/1999/xhtml">
    <xsl:attribute name="xml:lang"><xsl:value-of select="$lang" /></xsl:attribute>
    <xsl:attribute name="lang"><xsl:value-of select="$lang" /></xsl:attribute>
  <head>
    <title>
      <xsl:value-of select="$preview-title" />
      <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
      <xsl:value-of select="document/year" />
      <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
      <xsl:value-of select="$for-family" /> 
      <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
      <xsl:value-of select="document/familylastname" disable-output-escaping="yes" />
      <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
      <xsl:value-of select="$to" />
      <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
      <xsl:value-of select="document/currentdate" />
    </title> 
    <style type="text/css" media="all">
      <xsl:value-of select="document/stylesheet" />
    </style>
  </head>
  <body>
    <div id="page">
      <xsl:apply-templates select="document/billslist" />
      <xsl:apply-templates select="document/url" />
    </div>
  </body>
  </html>
</xsl:template>

<xsl:template match="billslist">
<div class="FamilyAnnualBill">
  <h2>
    <xsl:value-of select="$family" />
    <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
    <xsl:value-of select="../../document/familylastname" disable-output-escaping="yes" />
  </h2>

  <table cellspacing="0">
    <tr>
      <th colspan="7" class="Year"><xsl:value-of select="../../document/year" /></th>
    </tr>
    <tr> 
      <th><xsl:value-of select="$month" /></th>
      <th><xsl:value-of select="$payment-amount" /></th>
      <th><xsl:value-of select="$payment-mode" /></th>
      <th><xsl:value-of select="$bank" /></th>
      <th><xsl:value-of select="$check-nb" /></th>
      <th><xsl:value-of select="$paid-amount" /></th>
      <th><xsl:value-of select="$payment-date" /></th>
    </tr>

    <xsl:for-each select="monthlybill">
    <tr>
      <xsl:choose>
        <xsl:when test="position() = 1">
          <td><xsl:value-of select="$balance" /></td>
        </xsl:when>
        <xsl:otherwise>
          <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>  
        </xsl:otherwise>
      </xsl:choose>
      <td class="PreviousBalance">
        <xsl:value-of select="bill/detailsbill/billpreviousbalance" />
        <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
        <xsl:value-of select="$payment-unit" />
      </td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
    </tr>
    <tr>
      <td><xsl:value-of select="bill/detailsbill/billmonthyear" /></td>
      <td class="BillSubTotalAmount">
        <xsl:value-of select="bill/detailsbill/billsubtotalamount" />
        <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
        <xsl:value-of select="$payment-unit" />
      </td>
      <td>
        <xsl:choose>
          <xsl:when test="count(paymentsbill/payment) = 0">
            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:for-each select="paymentsbill/payment">  
              <xsl:choose>
                <xsl:when test="paymentmodeid = 0">
                  <xsl:value-of select="$money" />
                </xsl:when>
                <xsl:when test="paymentmodeid = 1">
                  <xsl:value-of select="$check" />
                </xsl:when>
                <xsl:when test="paymentmodeid = 2">
                  <xsl:value-of select="$bank-transfert" />
                </xsl:when>
                <xsl:when test="paymentmodeid = 3">
                  <xsl:value-of select="$credit-card" />
                </xsl:when>
              </xsl:choose>
              <xsl:if test="position() != last()">
                <br />
              </xsl:if>
            </xsl:for-each>
          </xsl:otherwise>
        </xsl:choose>
      </td>
      <td>
        <xsl:choose>
          <xsl:when test="count(paymentsbill/payment) = 0">
            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:for-each select="paymentsbill/payment">
              <xsl:choose>
                <xsl:when test="bankacronym != ''">
                  <xsl:value-of select="bankacronym" disable-output-escaping="yes" />
                </xsl:when>
                <xsl:otherwise>
                  <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
                </xsl:otherwise>
              </xsl:choose>
              <xsl:if test="position() != last()">
                <br />
              </xsl:if>
            </xsl:for-each>
          </xsl:otherwise>
        </xsl:choose>
      </td>
      <td class="CheckNb">
        <xsl:choose>
          <xsl:when test="count(paymentsbill/payment) = 0">
            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:for-each select="paymentsbill/payment">
              <xsl:choose>
                <xsl:when test="paymentchecknb != ''">
                  <xsl:value-of select="paymentchecknb" />
                </xsl:when>
                <xsl:otherwise>
                  <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
                </xsl:otherwise>
              </xsl:choose>
              <xsl:if test="position() != last()">
                <br />
              </xsl:if>
            </xsl:for-each>
          </xsl:otherwise>
        </xsl:choose>
      </td>
      <td class="PaymentAmount">
        <xsl:choose>
          <xsl:when test="count(paymentsbill/payment) = 0">
            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:for-each select="paymentsbill/payment">
              <xsl:value-of select="paymentamount" />
              <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
              <xsl:value-of select="$payment-unit" />
              <xsl:if test="position() != last()">
                <br />
              </xsl:if>
            </xsl:for-each>
          </xsl:otherwise>
        </xsl:choose>
      </td>
      <td>
        <xsl:choose>
          <xsl:when test="count(paymentsbill/payment) = 0">
            <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
          </xsl:when>
            <xsl:otherwise>
            <xsl:for-each select="paymentsbill/payment">
              <xsl:value-of select="paymentdate" />
              <xsl:if test="position() != last()">
                <br />
              </xsl:if>
            </xsl:for-each>
          </xsl:otherwise>
        </xsl:choose>
      </td>
    </tr>
    </xsl:for-each>
    <tr>
      <td><xsl:value-of select="$balance" /></td>
      <td class="Balance">
        <xsl:value-of select="../../document/familybalance" />
        <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
        <xsl:value-of select="$payment-unit" />
      </td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
      <td><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></td>
    </tr>
    <tr>
      <td class="TableFooter" colspan="7">
        <xsl:value-of select="$footer-part-1" />
        <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
        <xsl:value-of select="../../document/currentdate" />.
        <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
        <xsl:value-of select="$footer-part-2" />
      </td>
    </tr>
  </table>
</div>
</xsl:template>

<xsl:template match="url">
  <p id="backlink">
    <a>
      <xsl:attribute name="href"><xsl:value-of select="link" /></xsl:attribute>
      <xsl:value-of select="text" />
    </a>
  </p>
</xsl:template>

</xsl:stylesheet>