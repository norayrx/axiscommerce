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
 * @package     Axis_Sitemap
 * @copyright   Copyright 2008-2012 Axis
 * @license     GNU Public License V3.0
 */

class Axis_Sitemap_Upgrade_0_1_4 extends Axis_Core_Model_Migration_Abstract
{
    protected $_version = '0.1.4';

    public function up()
    {
        $paths = array(
            'sitemap/main/frequency',
            'sitemap/categories/frequency',
            'sitemap/products/frequency',
            'sitemap/cms/frequency'
        );
        $rowset = Axis::single('core/config_field')->select()
            ->where('path IN (?)', $paths)
            ->fetchRowset();
        
        foreach ($rowset as $row) {
            $row->model = 'sitemap/option_frequency';
            $row->save();
        }
    }
}