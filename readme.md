# EIA Fuel Surcharge Display

A WordPress plugin that retrieves diesel fuel prices from the U.S. Energy Information Administration (EIA) API and calculates fuel surcharge rates for display on your website through flexible shortcodes.

## Development Setup

### Prerequisites

- WordPress 5.2 or higher
- PHP 7.2 or higher
- [Composer](https://getcomposer.org/) (for dependency management)
- API key from [EIA Open Data](https://www.eia.gov/opendata/)

### Installation for Development

1. Clone this repository to your local machine:
   ```
   git clone https://github.com/yourusername/eia-fuel-surcharge.git
   ```

2. Navigate to the plugin directory:
   ```
   cd eia-fuel-surcharge
   ```

3. Install dependencies:
   ```
   composer install
   ```

4. Copy the plugin to your WordPress plugins directory or create a symlink for development.

### Building the Plugin

The repository includes a build script to create a zip file suitable for installing in WordPress.

1. Make the build script executable:
   ```
   chmod +x build-plugin.sh
   ```

2. Run the build script:
   ```
   ./build-plugin.sh
   ```
   
   You can specify a version number as an argument:
   ```
   ./build-plugin.sh 1.0.1
   ```

3. The plugin zip file will be created in the `dist` directory and can be installed via the WordPress admin interface.

## Usage

After installing and activating the plugin:

1. Go to the "Fuel Surcharge" menu in the WordPress admin.
2. Enter your EIA API key on the Settings page.
3. Configure the calculation formula and display options.
4. Use the shortcodes on your website:
   - `[fuel_surcharge]` - Display the current fuel surcharge rate
   - `[fuel_surcharge_table]` - Display a table of historical rates

## Shortcode Parameters

### [fuel_surcharge]

| Parameter | Description | Default | Example |
|-----------|-------------|---------|---------|
| format | Custom text format | From settings | `format="Fuel Surcharge: {rate}%"` |
| date_format | PHP date format | From settings | `date_format="F j, Y"` |
| decimals | Decimal places | From settings | `decimals="1"` |
| class | CSS class | `fuel-surcharge` | `class="highlight-box"` |
| region | Data region | From settings | `region="east_coast"` |
| compare | Comparison period | From settings | `compare="month"` |
| show_comparison | Show comparison | From settings | `show_comparison="false"` |

### [fuel_surcharge_table]

| Parameter | Description | Default | Example |
|-----------|-------------|---------|---------|
| rows | Number of rows | From settings | `rows="5"` |
| date_format | PHP date format | From settings | `date_format="F j, Y"` |
| columns | Columns to display | `date,price,rate` | `columns="date,rate"` |
| order | Sort order | `desc` | `order="asc"` |
| class | CSS class | `fuel-surcharge-table` | `class="striped"` |
| region | Data region | From settings | `region="midwest"` |
| title | Table title | None | `title="Historical Rates"` |
| show_footer | Show formula in footer | `false` | `show_footer="true"` |
| decimals | Decimal places | From settings | `decimals="1"` |

## License

This project is licensed under the GPL v2 or later - see the LICENSE file for details.

## Credits

This plugin utilizes the U.S. Energy Information Administration (EIA) API to retrieve diesel fuel price data.