<?php
/**
 * Tax Rate Updater plugin for Craft CMS
 *
 * TaxRateUpdater Service
 *
 * @author    Nathaniel Hammond - @nfourtythree - webdna
 * @copyright Copyright (c) 2017 webdna
 * @link      http://webdna.co.uk
 * @package   TaxRateUpdater
 * @since     1.0.0
 */

namespace Craft;

class TaxRateUpdaterService extends BaseApplicationComponent
{
  protected $apiUrl = 'http://api.zip-tax.com/request/v20';
  protected $plugin;
  protected $pluginHandle = 'taxRateUpdater';
  protected $settings;
  protected $existingTaxZonesByStates;

  public function __construct()
  {
    $this->plugin = craft()->plugins->getPlugin( $this->pluginHandle );
    $this->settings = $this->plugin->getSettings();
  }

  /**
   * Update Tax Rates main function to update rates
   *
   */
  public function updateTaxRates()
  {
    // Get the states we need to work with
    $states = $this->_getStates();

    // Retrieve tax data from api for each state
    foreach ( $states as $state ) {

      $taskSettings = array(
        'state' => $state,
      );

      craft()->tasks->createTask('TaxRateUpdater', Craft::t("Updating / Creating Tax Rate for ") . $state->state, $taskSettings );

    }

  }

  /**
   * Update Create Tax Rate - update or create a tax rate based on the state provided
   * @param  TaxRateUpdater_StateModel $state
   * @return boolean
   */
  public function updateCreateTaxRate( $state )
  {
    $apiResponse = $this->_callApi( $state );

    $taxData = $this->_getTaxDataFromApiResponse( $apiResponse );

    if ( $taxData ) {
      // Update tax records in the database

      // Crate quick array of states that already have zones
      // for quicker checking later
      $this->existingTaxZonesByStates = $this->_getExistingTaxZonesByStates();

      // Check to see if there is a zone
      if ( !array_key_exists( $state->code, $this->existingTaxZonesByStates ) ) {

        // Create new tax zone for state
        $taxZone = $this->_createTaxZone( $state );

        $taxZoneId = $taxZone->id;

      } else {
        $taxZoneId = $this->existingTaxZonesByStates[ $state->code ];
      }

      // Check to see if there is an existing rate
      $taxRates = craft()->commerce_taxRates->getAllTaxRates(
        array(
          'with' => [ 'taxZone' ],
          'condition' => 'taxZoneId=' . $taxZoneId
        )
      );

      if ( count( $taxRates ) ) {
        // Setup tax rate variable to update existing rate
        $taxRate = $taxRates[ 0 ];
      } else {
        // Create a brand new tax rate record
        $taxRate = $this->_createTaxRate( $state, $taxZoneId );
      }

      // Add new rate to the tax rate model
      $taxRate->rate = $taxData->stateSalesTax;

      // Save Tax Rate
      craft()->commerce_taxRates->saveTaxRate( $taxRate );

      return true;
    }

    return false;
  }

  /**
   * Create new tax zone base on state config
   * @param  Mixed $state             State array that comes from the config file (either default or user)
   * @return Commerce_TaxZoneModel    A filled out tax zone model with the new data from the state config
   */
  private function _createTaxZone( $state )
  {
    if ( $state ) {

      // Get commerce state for the ID
      $commerceState = craft()->commerce_states->getStateByAttributes( array(
        'abbreviation' => $state->code,
      ) );

      if ( $commerceState ) {
        $stateIds = array( $commerceState->id );
      } else {
        throw new Exception( Craft::t( 'Unable to find {state} in Craft Commerce states', array( 'state' => $state->state ) ) );
      }

      // Create Zone model
      $newTaxZoneModel = Commerce_TaxZoneModel::populateModel( array(
        'name' => $state->state,
        'description' => '',
        'countryBased' => 0,
      ) );

      // Create new tax zone
      $newTaxZoneResult = craft()->commerce_taxZones->saveTaxZone( $newTaxZoneModel, array() /* countryIds */ , $stateIds );

      // If we successfully created a tax zone we need to retrieve it
      // as the function doesn't return us the model
      if ( $newTaxZoneResult ) {
        $this->existingTaxZonesByStates = $this->_getExistingTaxZonesByStates();

        if ( array_key_exists( $state->code, $this->existingTaxZonesByStates ) ) {
          // return tax zone modal
          return craft()->commerce_taxZones->getTaxZoneById( $this->existingTaxZonesByStates[ $state->code ] );
        } else {
          throw new Exception( Craft::t ( 'Unable to find Zone in existingTaxZonesByStates' ) );
        }
      } else {
        throw new Exception( Craft::t( 'Error creating Tax Zone {state}', array( 'state' => $state->state ) ) );
      }


    } else {
      throw new Exception( Craft::t( 'Valid state config data required' ) );
    }

    return $newTaxZone;
  }

  /**
   * Create Tax Rate for state config
   * @param  Mixed  $state          State config array
   * @param  Int    $taxZoneId      ID of the tax zone to associate the rate to
   * @return Commerce_TaxRateModel
   */
  private function _createTaxRate( $state, $taxZoneId )
  {
    // Get applicable tax category from config value
    $taxCategory = craft()->commerce_taxCategories->getTaxCategoryByHandle( $state->category );

    // Carry on if we have a tax category to work with
    if ( $taxCategory ) {

      // Create new rate
      $taxRate = Commerce_TaxRateModel::populateModel( array(
        'taxZoneId' => $taxZoneId,
        'taxCategoryId' => $taxCategory->id,
        'name' => $state->state,
        'include' => $state->include,
        'taxable' => $state->taxable,
      ) );

    } else {
      throw new Exception( Craft::t( 'Unable to retrieve the tax category for {state}.', array( 'state' => $state->state ) ) );
    }

    return $taxRate;
  }

  /**
   * Get an array of Zones that already exist
   * @return Array an array of existing zones indexed by abbreviation
   */
  private function _getExistingTaxZonesByStates()
  {
    // Get all current zones
    $allTaxZones = craft()->commerce_taxZones->getAllTaxZones();

    $taxZonesExists = array();

    foreach ( $allTaxZones as $zone ) {
      $zoneStates = $zone->getStates();

      if ( count( $zoneStates ) ) {
        foreach ( $zoneStates as $zoneState ) {
          $taxZonesExists[ $zoneState->abbreviation ] = $zone->id;
        }
      }
    }

    return $taxZonesExists;
  }

  /**
   * Get Tax Data from API Response feeds in the json decoded response for spitting out tax data object
   * @param  Object $apiResponse json decoded response object (http://docs.zip-tax.com/en/latest/api_response.html#json-sample-response)
   * @return Object              Tax Data object from API
   */
  private function _getTaxDataFromApiResponse( $apiResponse )
  {
    // Work with it if we have a valid response
    if ( $apiResponse ) {
      if ( $apiResponse->rCode == '100') {
        // Get first result, it should be the one we are looking
        if ( isset( $apiResponse->results[ 0 ] ) ) {
          // Return Tax Data
          return $apiResponse->results[ 0 ];
        } else {
          throw new Exception( Craft::t( 'There was no data in the result set from the API' ) );
        }
      } else {
        throw new Exception( Craft::t( 'Error code form API ' . $apiResponse->rCode ) );
      }
    } else {
      throw new Exception( Craft::t( 'Unable to parse API response' ) );
    }
  }

  /**
  * Call the Zip Tax API
  *
  * @param TaxRateUpdater_StateModel
  *
  * @return array (tax data)
  * @throws Exception
  */
  private function _callApi( TaxRateUpdater_StateModel $state )
  {
    // Debug
    // $jsonContents = json_decode( file_get_contents(CRAFT_PLUGINS_PATH . '/' . strtolower($this->pluginHandle) .'/resources/test/example-response.json' ) );
    // if ( $jsonContents ) {
    //   return $jsonContents;
    // }

    if ( $state->code and $state->zip ) {
      // Build api request url
      $queryArray = $this->_buildRequestQueryArray( $state );

      // Proceed if we have an api url
      if ( $this->apiUrl ) {

        // Create a new curl client and get a response
        $client = new \Guzzle\Http\Client();

        $urlQueryString = '';
        if ( is_array( $queryArray ) and count( $queryArray ) ) {
          $urlQueryString = '?';
          $tmpArray = array();
          foreach ($queryArray as $key => $value) {
            $tmpArray[] = urlencode( $key ) . '=' . urlencode( $value );
          }

          $urlQueryString .= implode( '&' , $tmpArray );
        }

        TaxRateUpdaterPlugin::log( $this->apiUrl . $urlQueryString, LogLevel::Info);
        $response = $client->get( $this->apiUrl . $urlQueryString )->send();

        if ($response->isSuccessful()) {
          $responseData = $response->getBody();
          $responseData = json_decode( $responseData );
          return $responseData;
        } else {
          throw new Exception( Craft::t( 'Error retrieving data from the API' ) );
        }
      } else {
        throw new Exception( Craft::t( 'Please specify an api url' ) );
      }

    } else {
      throw new Exception( Craft::t( 'To retrieve data from the API both a state code and state zip are required' ) );
    }
  }

  /**
   * Build Request Query Array for Zip Tax API
   * @param  TaxRateUpdater_StateModel $state
   * @return Array                            request URL
   */
  private function _buildRequestQueryArray( TaxRateUpdater_StateModel $state )
  {

    if ( $this->settings->zipTaxApiKey ) {

      $query = array(
        'key' => $this->settings->zipTaxApiKey,
        'format' => 'JSON',
        'postalcode' => $state->zip,
        'state' => $state->code,
      );

      return $query;

    } else {
      throw new Exception( Craft::t( 'Please provide a zip tax API key' ) );
    }
  }

  /**
  * Populate the state models from the config data, either default or user
  */
  public function _getStates()
  {
    $states = craft()->config->get( 'states', 'taxrateupdater' );

    return TaxRateUpdater_StateModel::populateModels( $states );
  }

}
