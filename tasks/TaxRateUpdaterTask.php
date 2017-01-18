<?php
/**
 * Tax Rate Updater plugin for Craft CMS
 *
 * TaxRateUpdater Task
 *
 * @author    Nathaniel Hammond - @nfourtythree - webdna
 * @copyright Copyright (c) 2017 webdna
 * @link      https://webdna.co.uk
 * @package   TaxRateUpdater
 * @since     1.0.0
 */

namespace Craft;

class TaxRateUpdaterTask extends BaseTask
{
    /**
     * Defines the settings.
     *
     * @access protected
     * @return array
     */

    protected function defineSettings()
    {
        return array(
            'state' => AttributeType::Mixed,
        );
    }

    /**
     * Returns the default description for this task.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'TaxRateUpdater Update Tax Rate';
    }

    /**
     * Gets the total number of steps for this task.
     *
     * @return int
     */
    public function getTotalSteps()
    {
        return 1;
    }

    /**
     * Runs a task step.
     *
     * @param int $step
     * @return bool
     */
    public function runStep( $step )
    {
      $settings = $this->getSettings();

      if ( isset( $settings->state ) ) {
        TaxRateUpdaterPlugin::log( Craft::t( 'Running update create for ' ) . $settings->state->state, LogLevel::Info );
        return craft()->taxRateUpdater->updateCreateTaxRate( $settings->state );
      }

      TaxRateUpdaterPlugin::log( Craft::t( 'Error running task' ), LogLevel::Error);
      return false;
    }
}
