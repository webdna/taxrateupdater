<?php
/**
 * Tax Rate Updater plugin for Craft CMS
 *
 * TaxRateUpdater_State Model
 *
 *
 * @author    Nathaniel Hammond - @nfourtythree - webdna
 * @copyright Copyright (c) 2017 webdna
 * @link      http://webdna.co.uk
 * @package   TaxRateUpdater
 * @since     1.0.0
 */

namespace Craft;

class TaxRateUpdater_StateModel extends BaseModel
{
    /**
     * Defines this model's attributes.
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), array(
            'state'     => array(AttributeType::String, 'default' => ''),
            'category'     => array(AttributeType::String, 'default' => ''),
            'code'     => array(AttributeType::String, 'default' => ''),
            'include'     => array(AttributeType::Number, 'default' => ''),
            'taxable'     => array(AttributeType::String, 'default' => ''),
            'zip'     => array(AttributeType::String, 'default' => ''),
        ));
    }

}
