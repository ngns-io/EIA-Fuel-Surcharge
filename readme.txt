=== EIA Fuel Surcharge Display ===
Contributors: yourname
Donate link: https://example.com/donate
Tags: fuel surcharge, diesel, transportation, logistics, eia
Requires at least: 5.2
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display fuel surcharge rates on your website based on official EIA diesel price data.

== Description ==

The EIA Fuel Surcharge Display plugin retrieves diesel fuel prices from the U.S. Energy Information Administration (EIA) API and calculates fuel surcharge rates for display on your website.

This plugin is perfect for transportation, logistics, and shipping companies that need to display updated fuel surcharge rates on their websites.

= Features =

* Automatically retrieves the latest diesel fuel prices from the official EIA API
* Calculates fuel surcharge rates based on customizable formulas
* Displays current fuel surcharge rates via shortcode
* Displays historical fuel surcharge data in table format
* Flexible scheduling options for data updates
* Fully customizable display options
* Detailed logging and error reporting
* Easy to use admin interface

= Shortcodes =

* `[fuel_surcharge]` - Display the current fuel surcharge rate as text
* `[fuel_surcharge_table]` - Display a table of historical fuel surcharge rates

= Shortcode Parameters =

**[fuel_surcharge]**

* `format` - Custom text format (default: "Currently as of {date} the fuel surcharge is {rate}%")
* `date_format` - PHP date format (default: settings value or "m/d/Y")
* `decimals` - Number of decimal places for rate (default: settings value or 2)
* `class` - CSS class for the output element (default: "fuel-surcharge")

**[fuel_surcharge_table]**

* `rows` - Number of rows to display (default: 10)
* `date_format` - PHP date format (default: settings value or "m/d/Y")
* `columns` - Columns to display, comma-separated (options: date,price,rate) (default: all)
* `order` - Sort order (options: asc,desc) (default: desc)
* `class` - CSS class for the table (default: "fuel-surcharge-table")

= Requirements =

* WordPress 5.2 or higher
* PHP 7.2 or higher
* EIA API Key (free, available at https://www.eia.gov/opendata/)

== Installation ==

1. Upload the `eia-fuel-surcharge` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Fuel Surcharge' in the admin menu
4. Enter your EIA API Key (obtain one at https://www.eia.gov/opendata/)
5. Configure your settings and scheduling options
6. Use the shortcodes to display fuel surcharge information on your site

== Frequently Asked Questions ==

= Where do I get an EIA API Key? =

You can register for a free EIA API Key at [https://www.eia.gov/opendata/](https://www.eia.gov/opendata/).

= How is the fuel surcharge calculated? =

By default, the fuel surcharge is calculated using the following formula:
Surcharge = ((Current Diesel Price - Base Threshold) / Increment Amount) * Percentage Rate

For example, with default settings:
Base Threshold: $1.20
Increment Amount: $0.06
Percentage Rate: 0.5%

If the current diesel price is $4.00, the calculation would be:
Surcharge = (($4.00 - $1.20) / $0.06) * 0.5% = (46.67) * 0.5% = 23.33%

These settings can be customized in the plugin settings.

= How often is the fuel surcharge data updated? =

The EIA typically updates diesel price data weekly, with new data released every Monday. The plugin allows you to configure your update schedule to align with this. By default, it's set to update weekly on Tuesdays (to ensure the latest data is available).

You can customize the update frequency in the plugin settings to daily, weekly, monthly, or a custom interval.

= Can I manually update the fuel surcharge data? =

Yes, you can manually trigger an update from the plugin's admin page by clicking the "Update Now" button.

= How do I change the display format? =

You can customize the display format in the plugin settings or by using the `format` parameter in the shortcode. The format uses placeholders `{date}` and `{rate}` which are replaced with the actual values.

== Screenshots ==

1. Admin settings page
2. Data & History page
3. Logs page
4. Example of the fuel surcharge display on a website
5. Example of the fuel surcharge table on a website

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release

== Credits ==

This plugin utilizes the U.S. Energy Information Administration (EIA) API to retrieve diesel fuel price data.