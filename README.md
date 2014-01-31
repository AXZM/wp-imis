# Wordpress iMIS Membership

This plugin connects iMIS15 to Wordpress allowing users to authenticate with an asmx server through SOAP 1.2. This plugin is based on [Membership with iMIS and memberCMS by NonProfitCMS](http://wordpress.org/plugins/membership-with-imis-and-membercms/) but has removed the use of NuSOAP in favor of the PHP5 SOAP Client.

## Installation

1. Clone this repo into your plugins directory
2. Activate the plugin
3. Navigate to the `iMIS` item on the left hand Wordpress menu
4. Set the Login page
5. Set the application end point (This is the URL before the `AsiCommon/Services/Membership/MembershipWebService.asmx?WSDL`)
6. Navigate to a page and use the selector drop down to force a page to be authenticated