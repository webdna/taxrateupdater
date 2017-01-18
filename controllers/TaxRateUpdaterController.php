<?php
/**
 * Tax Rate Updater plugin for Craft CMS
 *
 * TaxRateUpdater Controller
 *
 * @author    Nathaniel Hammond - @nfourtythree - webdna
 * @copyright Copyright (c) 2017 webdna
 * @link      http://webdna.co.uk
 * @package   TaxRateUpdater
 * @since     1.0.0
 */

namespace Craft;

class TaxRateUpdaterController extends BaseController
{

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = array(
      'actionIndex',
    );

    /**
     * Handle a request going to our plugin's index action URL, e.g.: actions/taxRateUpdater
     */
    public function actionIndex()
    {
      craft()->taxRateUpdater->updateTaxRates();

      echo 'Update Tax Rates Tasks Started';
      craft()->end();
    }

}
