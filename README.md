# Tax Rate Updater plugin for Craft CMS

Updating Craft Commerce US tax rates from the [Zip Tax API](http://zip-tax.com/)

## Installation

To install Tax Rate Updater, follow these steps:

1. Download & unzip the file and place the `taxrateupdater` directory into your `craft/plugins` directory
2.  -OR- do a `git clone git@github.com/webdna/taxrateupdater` directly into your `craft/plugins` folder.  You can then update it with `git pull`
3. Install plugin in the Craft Control Panel under Settings > Plugins
4. The plugin folder should be named `taxrateupdater` for Craft to see it.  GitHub recently started appending `-master` (the branch name) to the name of the folder for zip file downloads.

Tax Rate Updater works on Craft 2.4.x and Craft 2.5.x.

## Tax Rate Updater Overview

This plugin is specifically linked to the [Zip Tax API](http://zip-tax.com/).

It will create / update Tax Rates for all states specified in the config, using data obtained from the Zip Tax API.

## Configuring Tax Rate Updater

You will require an API key from Zip Tax. Once you have signed up and retrieved your key, head over to the plugin settings page in the control panel, enter you key and hit save.

To see an example of the config please check in the plugin folder in the [config.php](https://github.com/webdna/taxrateupdater/blob/master/config.php).

You can override this config in your craft config folder with a file named `taxrateupdater.php`. This file should having the following format:

```php
<?php

  return array(
    'states' => array( // Array of states
      array( // Each state array
        'state' => 'California', // Nice name for the state
        'code' => 'CA', // Two letter state code
        'zip' => '94111', // Zip code for the state
        'category' => 'general', // Craft Commerce tax category handle to add the rate to
        'include' => 0, // Boolean - whether the tax is included in the price of the products
        'taxable' => 'price' // Where the tax rate should apply, on the item, the shipping or both. Options: price, shipping, price_shipping
      )
    )
  );

```

## Using Tax Rate Updater

To initiate the updating / creating of tax rates simply hit the url `YOUR_DOMAIN/actions/taxRateUpdater`. This then goes ahead an creates the tasks that will update the rates

## Tax Rate Updater Roadmap

Some things to do, and ideas for potential features:

* Allow different APIs

## Tax Rate Updater Changelog

* [See Releases](https://github.com/webdna/taxrateupdater/releases)

Brought to you by [webdna](http://webdna.co.uk)
