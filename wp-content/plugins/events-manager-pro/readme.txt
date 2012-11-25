=== Events Manager Pro ===  
Contributors: netweblogic
Tags: events, event, event registration, event calendar, events calendar, event management, paypal, registration, ticket, tickets, ticketing, tickets, theme, widget, locations, maps, booking, attendance, attendee, buddypress, calendar, gigs, payment, payments, sports, 
Requires at least: 3.1
Tested up to: 3.2.1
Stable tag: 1.37

== Description ==

Thank you for downloading Events Manager Pro!

Please check these pages for further information:

http://wp-events-plugin.com/documentation/ - lots of docs to help get you started
http://wp-events-plugin.com/tutorials/ - for advanced users, see how far you can take EM and Pro!

If you have any issues/questions with the plugin, or would like to request a feature, please visit:
http://wp-events-plugin.com/support/

== Installation ==

Please visit http://wp-events-plugin.com/documentation/installation/

== Changelog ==

= 1.37 = 
* allows negative manual payments
* paypal return url instructions corrected

= 1.36 =
* fixed bug which prevented transaction tables showing unregistered/deleted users.
* warning added if EM plugin version is too low
* update notices appear on the network admin area as well
* added cron tasks for paypal booking timeouts
* added return url option for paypal
* custom booking form information properly escaped and filtered
* paypal manual approvals won't take effect with normal approvals disabled
* offline and paypal pending spaces taken into account
* paypal and offline payments take tax into account (requires EM 4.213)
* fixed logo not being shown on paypal payment page
* payments in no-user mode accepted (requires EM 4.213)

= 1.35 =
* added alternative notification check for servers with old SSL Certificates
* added dev mode updates option in the events setttings page
* removed the main gateway JS
* manual bookings can now be done by all users with the right permissions
* paypal payments will not include free tickets during checkout paying, avoiding errors on paypal
* pot files updated
* German and Swedish translations updated
* fixed various warnings
* multiple alert boxes when confirming offline payments fixed