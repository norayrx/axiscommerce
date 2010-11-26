<?php
/**
 * Axis
 * 
 * This file is part of Axis.
 * 
 * Axis is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Axis is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Axis.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @category    Axis
 * @package     Axis_Catalog
 * @subpackage  Axis_Catalog_Model
 * @copyright   Copyright 2008-2010 Axis
 * @license     GNU Public License V3.0
 */

/**
 * 
 * @category    Axis
 * @package     Axis_Catalog
 * @subpackage  Axis_Catalog_Model
 * @author      Axis Core Team <core@axiscommerce.com>
 */
class Axis_Catalog_Model_Product_Option_Row extends Axis_Db_Table_Row
{
    public function getValuesArrayByLanguage($languageId)
    {
        if (!$this->valueset_id) {
            return array();
        }
        $select = $this->getAdapter()->select();
        $select->from(array('pov' => $this->_prefix . 'catalog_product_option_value'))
            ->join(
                array('povt' => $this->_prefix . 'catalog_product_option_value_text'),
                'pov.id = povt.option_value_id',
                'name'
            )
            ->where('pov.valueset_id = ' . $this->valueset_id)
            ->where('povt.language_id = ' . $languageId);
        return $select->query()->fetchAll();
    }
    
    public function isInputable()
    {
        if ($this->input_type == Axis_Catalog_Model_Product_Option::TYPE_STRING || 
            $this->input_type == Axis_Catalog_Model_Product_Option::TYPE_TEXTAREA || 
            $this->input_type == Axis_Catalog_Model_Product_Option::TYPE_FILE)
            return true;
        return false;
    }
    
    public function getValueset()
    {
        if ($this->valueset_id) {
            return $this->findParentRow('Axis_Catalog_Model_Product_Option_ValueSet', 'ValueSet');
        }
        return null;
    }
}