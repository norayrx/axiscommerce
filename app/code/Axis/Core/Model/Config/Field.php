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
 * @package     Axis_Core
 * @subpackage  Axis_Core_Model
 * @copyright   Copyright 2008-2011 Axis
 * @license     GNU Public License V3.0
 */

/**
 *
 * @category    Axis
 * @package     Axis_Core
 * @subpackage  Axis_Core_Model
 * @author      Axis Core Team <core@axiscommerce.com>
 */
class Axis_Core_Model_Config_Field extends Axis_Db_Table
{
    protected $_name = 'core_config_field';

    protected $_primary = 'id';

    protected $_rowClass = 'Axis_Core_Model_Config_Field_Row';

    protected $_selectClass = 'Axis_Core_Model_Config_Field_Select';

    /**
     * Insert or update config field
     *
     * @param array $data
     * @return Axis_Db_Table_Row
     */
    public function save(array $data)
    {
        $row = $this->select()
            ->where('path = ?', $data['path'])
            ->fetchRow();

        if (!$row) {
            $row = $this->createRow();
        } 
        $row->setFromArray($data);
        
        $row->lvl = count(explode('/', $row->path));
        
        if ($row->lvl <= 2) {
            $row->type = '';
        }
        
        $row->save();
        
        return $row;
    }

    /**
     * Removes config field, and all of it childrens
     * Provide fluent interface
     * @param string $path
     * @return bool
     */
    public function remove($path)
    {
        $this->delete("path LIKE '{$path}%'");
        Axis::single('core/config_value')->remove($path);
        return $this;
    }
    
    protected $_datarowset = array();

    /**
     * Add config field row, and config value,
     * if $data['path'] has third level of config
     *
     * @param string path 'root'|'root/branch/config_field'
     * @param string title 'root'|'root/branch/config_field'
     * @param string $value Config field value
     * @param string $type 'bool|multiple|string|select|text|handler'
     * @param string $description Config field description
     * @param array $data
     *  model => ''
     * @return Axis_Core_Model_Config_Field Provides fluent interface
     */
    public function add(
            $path,
            $title,
            $value = '',
            $type = 'text',
            $description = '',
            $data = array())
    {
        $configEntries = explode('/', $path);
        $checkBeforeInsert = true;
        $title = explode('/', $title);

        if (is_array($description)) {
            $data = $description;
            $description = '';
        }

        $rowData = array('lvl' => 0);
        foreach ($configEntries as $configEntry) {
            if (++$rowData['lvl'] == 1) {
                $rowData['path'] = $configEntry;
            } else {
                $rowData['path'] .= '/' . $configEntry;
            }

            $rowData['title'] = isset($title[$rowData['lvl']-1]) ?
                $title[$rowData['lvl']-1] : $title[0];

            $rowData = array_merge(array(
                'type'        => 'text',
                'description' => '',
                'model'       => isset($data['model']) ? $data['model'] : '',
                'translation_module' => isset($data['translation_module']) ?
                    $data['translation_module'] : new Zend_Db_Expr('NULL')
            ), $rowData);

            if ($rowData['lvl'] == 3) {
                $rowData['type'] = $type;
                $rowData['description'] = $description;
                $rowData = array_merge($data, $rowData);
            }

            if ($checkBeforeInsert) {
                $rowField = $this->select()
                    ->where('path = ?', $rowData['path'])
                    ->fetchRow();
                if ($rowField) {
//                    continue;
                } else {
                    $checkBeforeInsert = false;
                }
            }
            $rowField = $this->createRow($rowData);
//            $rowField->save();
            if (!isset($this->_datarowset[$rowData['path']])) {
                $this->_datarowset[$rowData['path']] = array_merge($rowData, array(
                    'value' => $value
                ));
            }
        }

        if ($rowData['lvl'] == 3) {
            $modelValue = Axis::single('core/config_value');
            $rowValue = $modelValue->select()
                ->where('path = ?', $rowData['path'])
                ->fetchRow();
            if (!$rowValue) {
                $rowValue = $modelValue->createRow();
            }
            if (!empty($rowData['model'])) {
                $class = Axis::getClass($rowData['model']);
                if (class_exists($class) 
                    && in_array('Axis_Config_Option_Encodable_Interface', class_implements($class))) {

                    $value = Axis::model($rowData['model'])->encode($value);
                }
            }
            
            $rowValue->setFromArray(array(
                'config_field_id' => $rowField->id,
                'path'            => $rowData['path'],
                'site_id'         => 0,
                'value'           => $value
            ));
//            $rowValue->save();
        }

        return $this;
    }
    
    public function transform() 
    {
        $_defaultsRowField = array(
            'type'               => 'text',
            'description'        => '',
            'model'              => '',
        );
        
        $str = '';
        $container = array(); 
        foreach ($this->_datarowset as $option) {
            $tab = str_repeat(' ', 4);
            $tabs = str_repeat($tab, $option['lvl']);
            
            $isOption = (bool)($option['lvl'] == 3);
            
            $proporties = '';
            $title = '';
            if (!empty($option['title'])) {
                $title = "'{$option['title']}'";
//                $proporties .= "{$tabs}{$tab}->setTitle(" . $title . ")\n"; $title = '';
                $title = empty($title) ? '' : ', ' . $title;
            }
            $value = '';
            if ($isOption /*&& !empty($option['value'])*/) {
                $value = is_string($option['value']) ? "'{$option['value']}'" : $option['value'];
//                $proporties .= "{$tabs}{$tab}->setValue(" . $value . ")\n"; $value = '';
                $value = empty($option['value']) ? '' : ', ' . $value;
            }
            if ($isOption && $option['type'] !== $_defaultsRowField['type']) {
                $proporties .= "{$tabs}{$tab}->setType('" . $option['type'] . "')\n";
            }
            if ($option['description'] !== $_defaultsRowField['description']) {
                $proporties .= "{$tabs}{$tab}->setDescription('" . $option['description'] . "')\n";
            }
            if ($isOption && $option['model'] !== $_defaultsRowField['model']) {
                $proporties .= "{$tabs}{$tab}->setModel('" . $option['model'] . "')\n";
            }
            $tm = (string) $option['translation_module'];
            if (!empty($tm) && $tm !== 'NULL') {
                $proporties .= "{$tabs}{$tab}->setTranslation('" . $option['translation_module'] . "')\n";
            }
            
            $paths = explode('/', $option['path']);
            $path = array_pop($paths);
            
            
            if (!$isOption) {
                if (count($container) >= $option['lvl']) {
                    $k =  array_pop($container);
                    $tabsk = str_repeat($tab, 1 + min(array(count($container), $option['lvl'])));
                    $str .=  "{$tabsk}->section('/{$k}')\n\n" ;
                }
                array_push($container, $path);
            }
            
            $str .= "{$tabs}->" . ( $isOption ? 'option' : 'section') 
                 . "('" . $path . "'" . $title . $value . ")\n"; 
            
            if (!empty($proporties)) {
                $str  .= $proporties;
            }
        }
        Zend_Debug::dump(
            "\nAxis::single('core/config_builder')\n" 
                . $str 
                . "\n{$tab}->section('/');\n"
        );
        $this->_datarowset = array();
        return $this;
    }
}