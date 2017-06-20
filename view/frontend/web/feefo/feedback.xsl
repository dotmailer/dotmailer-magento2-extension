<?xml version="1.0" encoding="ISO-8859-1" ?> 
<?xml-stylesheet type="text/css" href="style.css"?>
 <html xsl:version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
    <xsl:variable name="feefostarsimageroot" select="'http://cdn.feefo.com/feefo/resources/images/rating'" />
<!--<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>FeeFo Feedback</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>-->
 <body>
     <div id="page" itemtype="http://schema.org/LocalBusiness" itemscope="itemscope">
     <!-- tr class=row -->
           <div class="comments">

               <xsl:for-each select="FEEDBACKLIST/SUMMARY">
                   <!--<h1><span itemprop="name">
                       <xsl:choose>
                           <xsl:when test="COUNT > 1">
                               <xsl:value-of select="concat(TITLE,' reviews')"/>
                           </xsl:when>
                           <xsl:otherwise>
                               <xsl:value-of select="concat(TITLE,' review')"/>
                           </xsl:otherwise>
                       </xsl:choose>
                   </span></h1>

               <span>
                   <a>
                       <xsl:attribute name="href"><xsl:value-of select="concat('http://www.feefo.com/feefo/viewvendor.jsp?logon=',VENDORLOGON)"/></xsl:attribute>
                       <img itemprop="image">
                           <xsl:attribute name="src"><xsl:value-of select="SUPPLIERLOGO"/></xsl:attribute>
                           <xsl:attribute name="alt">Feefologo</xsl:attribute>
                       </img>
                   </a>

                   <p class="supplier">
                       <div itemtype="http://schema.org/AggregateRating" itemscope="itemscope" itemprop="aggregateRating">
                           <span itemprop="ratingValue"><xsl:attribute name="content"><xsl:value-of select="AVERAGE"/>%</xsl:attribute></span>
                           <span itemprop="bestRating"><xsl:attribute name="content"><xsl:value-of select="BEST"/></xsl:attribute></span>
                           <span itemprop="worstRating"><xsl:attribute name="content"><xsl:value-of select="WORST"/></xsl:attribute></span>
                           Feedback for <xsl:value-of select="TITLE"/> has been compiled from <strong><span itemprop="reviewCount"><xsl:value-of select="TOTALSERVICECOUNT"/></span></strong> customer reviews</div>
                   </p>

               </span>-->
               </xsl:for-each>
               <table class="comm-table" style="border-collapse: collapse; border-spacing: 0;">
                   <tr>
                       <th width="150">Product Name</th>
                       <th width="100">Date</th>
                       <th width="90">Rating</th>
                       <th width="300">Comment</th>
                   </tr>
                   <xsl:for-each select="FEEDBACKLIST/FEEDBACK">

                       <tr itemtype="http://schema.org/Review" itemscope="itemscope" itemprop="review">
                       <td style="padding-top: 5px;">
                           <!--<h3 class="item">-->
                               <xsl:choose>
                                   <xsl:when test="LINK">
                                       <a>
                                           <xsl:attribute name="href"><xsl:value-of select="LINK" disable-output-escaping="yes"/></xsl:attribute>
                                           <span><xsl:value-of select="DESCRIPTION" disable-output-escaping="yes"/></span>
                                       </a>
                                   </xsl:when>
                                   <xsl:otherwise>
                                       <span><xsl:value-of select="DESCRIPTION" disable-output-escaping="yes"/></span>
                                   </xsl:otherwise>
                               </xsl:choose>
                               <span itemprop="name"><xsl:attribute name="content"><xsl:value-of select="/FEEDBACKLIST/SUMMARY/TITLE" disable-output-escaping="yes"/></xsl:attribute></span>
                           <!--</h3>-->

                           <!--<xsl:if test="ADDITIONALITEMS">
                               <span class="alsobought"><ul>
                                   <li>Customer also bought: .... :</li>
                                   <xsl:for-each select="ADDITIONALITEMS/ITEM">
                                       <li><xsl:value-of select="." disable-output-escaping="yes" /></li>
                                   </xsl:for-each>
                               </ul></span>
                            </xsl:if>-->
                       </td>
                       <td style="padding-top: 5px;"><!-- time tag used here in HTML5 only might cause issues with legacy browsers -->
                           <a target="new"><xsl:attribute name="href"><xsl:value-of select="READMOREURL" disable-output-escaping="yes" /></xsl:attribute>
                               <time itemprop="datePublished"><xsl:attribute name="datetime"><xsl:value-of select="HREVIEWDATE" disable-output-escaping="yes" /></xsl:attribute><xsl:value-of select="DATE" disable-output-escaping="yes" /></time>
                           </a>
                       </td>
                       <td style="padding-top: 5px;">
                           <div class="comm-rating">
                           <!-- I added this, need to adapt with the below -->
                           <xsl:if test="SERVICERATING">
                               <xsl:if test="PRODUCTRATING">
                                   <em>Service:</em>
                                   </xsl:if>
                                   <xsl:variable name="serviceratingnumber">
                                       <xsl:choose>
                                           <xsl:when test="SERVICERATING = '++'">5</xsl:when>
                                           <xsl:when test="SERVICERATING = '+'">4</xsl:when>
                                           <xsl:when test="SERVICERATING = '-'">2</xsl:when>
                                           <xsl:when test="SERVICERATING = '--'">1</xsl:when>
                                           <xsl:when test="SERVICERATING = 'W'">W</xsl:when>
                                           <xsl:otherwise>norating</xsl:otherwise>
                                       </xsl:choose>
                                   </xsl:variable>
                                   <!-- this uses feefo images -->
                                   <img>
                                       <xsl:attribute name="src"><xsl:value-of select="concat($feefostarsimageroot,$serviceratingnumber,'.png')"/></xsl:attribute>
                                       <xsl:attribute name="alt"><xsl:value-of select="SERVICERATING"/></xsl:attribute>
                                   </img>
                                   <!-- the old method of displaying images using local assets has been removed -->
                                   <xsl:if test="not(SERVICELATEST)">
                                    <xsl:if test="$serviceratingnumber != '' and $serviceratingnumber != 'W' and $serviceratingnumber != 'norating'">
                                       <div itemtype="http://schema.org/Rating" itemscope="itemscope" itemprop="reviewRating">
                                           <meta content="1" itemprop="worstRating"/>
                                           <span itemprop="ratingValue"><xsl:attribute name="content"><xsl:value-of select="$serviceratingnumber"/></xsl:attribute></span>
                                           <span content="5" itemprop="bestRating"></span>
                                       </div>
                                    </xsl:if>
                                   </xsl:if>
                           </xsl:if>
                          <!-- I now need to sort out product, I've done service -->
                           <xsl:if test="string(PRODUCTRATING)">
                               <xsl:if test="SERVICERATING">
                                   <em>Product:</em>
                               </xsl:if>
                               <xsl:variable name="productratingnumber">
                                   <xsl:choose>
                                       <xsl:when test="PRODUCTRATING = '++'">5</xsl:when>
                                       <xsl:when test="PRODUCTRATING = '+'">4</xsl:when>
                                       <xsl:when test="PRODUCTRATING = '-'">2</xsl:when>
                                       <xsl:when test="PRODUCTRATING = '--'">1</xsl:when>
                                       <xsl:when test="PRODUCTRATING = 'W'">W</xsl:when>
                                       <xsl:otherwise>norating</xsl:otherwise>
                                   </xsl:choose>
                               </xsl:variable>

                               <img>
                                   <xsl:attribute name="src"><xsl:value-of select="concat($feefostarsimageroot,$productratingnumber,'.png')"/></xsl:attribute>
                                   <xsl:attribute name="alt"><xsl:value-of select="PRODUCTRATING"/></xsl:attribute>
                               </img>
                               <xsl:if test="HREVIEWRATING != ''">
                                   <xsl:if test="not(PRODUCTLATEST)">
                                       <xsl:if test="not(SERVICERATING)"> <!-- do the rating against the product in product only mode -->
                                           <div itemtype="http://schema.org/Rating" itemscope="itemscope" itemprop="reviewRating">
                                               <meta content="1" itemprop="worstRating"/>
                                               <span itemprop="ratingValue"><xsl:attribute name="content"><xsl:value-of select="HREVIEWRATING"/></xsl:attribute></span>
                                               <span content="5" itemprop="bestRating"></span>
                                           </div>
                                       </xsl:if>
                                   </xsl:if>
                               </xsl:if>

                            </xsl:if>
                               <xsl:if test="SERVICELATEST">
                                   <xsl:variable name="servicelatestnumber">
                                       <xsl:choose>
                                           <xsl:when test="SERVICELATEST = '++'">5</xsl:when>
                                           <xsl:when test="SERVICELATEST = '+'">4</xsl:when>
                                           <xsl:when test="SERVICELATEST = '-'">2</xsl:when>
                                           <xsl:when test="SERVICELATEST = '--'">1</xsl:when>
                                           <xsl:otherwise>norating</xsl:otherwise>
                                       </xsl:choose>
                                   </xsl:variable>
                                   <em>Latest:</em>
                                   <xsl:if test="PRODUCTRATING">
                                           <em>Service</em>
                                   </xsl:if>
                                   <img>
                                       <xsl:attribute name="src"><xsl:value-of select="concat($feefostarsimageroot,$servicelatestnumber,'.png')"/></xsl:attribute>
                                       <xsl:attribute name="alt"><xsl:value-of select="SERVICELATEST"/></xsl:attribute>
                                   </img>
                                   <xsl:if test="$servicelatestnumber != '' and $servicelatestnumber != 'W' and $servicelatestnumber != 'norating'">
                                       <div itemtype="http://schema.org/Rating" itemscope="itemscope" itemprop="reviewRating">
                                           <meta content="1" itemprop="worstRating"/>
                                           <span itemprop="ratingValue"><xsl:attribute name="content"><xsl:value-of select="$servicelatestnumber"/></xsl:attribute></span>
                                           <span content="5" itemprop="bestRating"></span>
                                       </div>
                                   </xsl:if>
                               </xsl:if>
                               <xsl:if test="PRODUCTLATEST and PRODUCTLATEST != '0'">
                                   <xsl:variable name="productlatestnumber">
                                       <xsl:choose>
                                           <xsl:when test="PRODUCTLATEST = '++'">5</xsl:when>
                                           <xsl:when test="PRODUCTLATEST = '+'">4</xsl:when>
                                           <xsl:when test="PRODUCTLATEST = '-'">2</xsl:when>
                                           <xsl:when test="PRODUCTLATEST = '--'">1</xsl:when>
                                           <xsl:otherwise>norating</xsl:otherwise>
                                       </xsl:choose>
                                   </xsl:variable>

                                   <xsl:if test="not(SERVICELATEST)">
                                       <em>Latest:</em>
                                   </xsl:if>
                                   <xsl:if test="SERVICERATING">
                                       <em>Product:</em>
                                   </xsl:if>
                                   <img>
                                       <xsl:attribute name="src"><xsl:value-of select="concat($feefostarsimageroot,$productlatestnumber,'.png')"/></xsl:attribute>
                                       <xsl:attribute name="alt"><xsl:value-of select="PRODUCTLATEST"/></xsl:attribute>
                                   </img>
                                   <xsl:if test="not(SERVICERATING)"> <!-- do the rating against the product in product only mode -->
                                       <div itemtype="http://schema.org/Rating" itemscope="itemscope" itemprop="reviewRating">
                                           <meta content="1" itemprop="worstRating"/>
                                           <span itemprop="ratingValue"><xsl:attribute name="content"><xsl:value-of select="$productlatestnumber"/></xsl:attribute></span>
                                           <span content="5" itemprop="bestRating"></span>
                                       </div>
                                   </xsl:if>
                               </xsl:if>
                           </div>
                       </td>
                       <td style="padding-top: 5px;">
                           <p style="margin:0;" itemprop="description">
                              <xsl:value-of select="CUSTOMERCOMMENT" disable-output-escaping="yes"/>
                           </p>
                           <!--<xsl:for-each select="FURTHERCOMMENTSTHREAD/POST">
                               <xsl:if test="CUSTOMERCOMMENT">
                                   <br />
                                   <div class="customercomment">
                                   <p>On <xsl:value-of select="DATE" /> the customer
                                       <xsl:if test="SERVICERATING or PRODUCTRATING"> changed their rating and </xsl:if>
                                       added:<br/> <xsl:value-of select="CUSTOMERCOMMENT" disable-output-escaping="yes" /></p>
                                   <a target="new"><xsl:attribute name="href"><xsl:value-of select="../../READMOREURL" disable-output-escaping="yes" /></xsl:attribute>See this exchange on Feefo </a>
                                   </div>
                               </xsl:if>
                               <xsl:if test="VENDORCOMMENT">
                                   <br />
                                   <div class="vendorcomment">
                                   <p>On <xsl:value-of select="DATE" /> the supplier responded:<br />
                                        <xsl:value-of select="VENDORCOMMENT" disable-output-escaping="yes" />
                                   </p>
                                   </div>
                               </xsl:if>
                           </xsl:for-each>-->

                       </td>
                       </tr>
                   </xsl:for-each>
               </table>
           </div>
       </div>
</body>
</html>
