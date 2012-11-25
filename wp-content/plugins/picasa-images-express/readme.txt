=== Plugin Name ===
Contributors: Scrawl
Donate link: http://www.msf.org/msfinternational/donations/
Tags: picasa, photo, image, insert, album, gallery
Requires at least: 2.5
Tested up to: 2.6.2
Stable tag: 1.1

Browse, search and insert photos from any public Picasa Web Albums into your Wordpress pages and posts.

== Description ==

Browse, search and insert photos from public Picasa Web Albums into your Wordpress pages and posts.

Watch the demo video at http://vimeo.com/1557869

Features include:

* Browse albums of any Picasa user who has public photos available
* Search for photos by caption and tags
* Choose to a insert single or multiple photos to make a gallery
* Lightbox / Thickbox support
* Display captions
* Supports all image sizes supported by the Picasa API
* All options are fully configurable when you're inserting photos

Requirements:

* Adobe Flash Player 9 (only during editing posts/pages. Your blog visitors do not require Flash) 

== Installation ==

1. Upload the `picasa-image-express` folder and all its contents to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Picasa Image Express and add your Picasa User Name

== Frequently Asked Questions ==

= How do you select multiple images? =

CTRL-click and SHIFT-click lets you select more than one photo once you're inside an album

= Why have you wrapped everything in Parapgraph nodes instead of Div? =

To stop TinyMCE scrambling the HTML when you switched between Visual and Source Code mode

= I'd like to over-ride the default stylesheet. Where is it? =

All the relevant CSS styles are located in picasa-image-express.css

= Is there a way to stop margins being added to each photo? =

Under settings > Picasa Image Express, find the margins option and delete the number from each field (ie make it blank). You can now control them via the stylesheet

== Screenshots ==

1. Click this icon to launch Picasa Image Express
2. Select the photos you wish to insert. Select multiple photos using CTRL-click and SHIFT-click
3. Options are available and configurable throughout the photo selection process
4. All options are also configurable via Setting > Picasa Image Express

== Version history ==

= 1.1 =

* Additional image size support:  288,320,400
* Thickbox now works properly
* Included MXML source as part of download

= 1.0 =

* First release