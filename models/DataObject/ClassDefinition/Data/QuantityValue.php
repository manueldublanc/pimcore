<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Cache;
use Pimcore\Cache\Runtime;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;

class QuantityValue extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface
{
    use Extension\ColumnType;
    use Extension\QueryColumnType;

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'quantityValue';

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $unitWidth;

    /**
     * @var float
     */
    public $defaultValue;

    /**
     * @var string
     */
    public $defaultUnit;

    /**
     * @var array
     */
    public $validUnits;

    /**
     * @var int
     */
    public $decimalPrecision;

    /**
     *
     * @var bool
     */
    public $autoConvert;

    /**
     * Type for the column to query
     *
     * @var int
     */
    public $queryColumnType = [
        'value' => 'double',
        'unit' => 'bigint(20)'
    ];

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = [
        'value' => 'double',
        'unit' => 'bigint(20)'
    ];

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = '\\Pimcore\\Model\\DataObject\\Data\\QuantityValue';

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getUnitWidth()
    {
        return $this->unitWidth;
    }

    /**
     * @param int $unitWidth
     */
    public function setUnitWidth($unitWidth)
    {
        $this->unitWidth = $unitWidth;
    }

    /**
     * @return int
     */
    public function getDefaultValue()
    {
        if ($this->defaultValue !== null) {
            return (float) $this->defaultValue;
        }
    }

    /**
     * @param int $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        if (strlen(strval($defaultValue)) > 0) {
            $this->defaultValue = $defaultValue;
        }
    }

    /**
     * @param  array $validUnits
     */
    public function setValidUnits($validUnits)
    {
        $this->validUnits = $validUnits;
    }

    /**
     * @return array
     */
    public function getValidUnits()
    {
        return $this->validUnits;
    }

    /**
     * @return string
     */
    public function getDefaultUnit()
    {
        return $this->defaultUnit;
    }

    /**
     * @param string $defaultUnit
     */
    public function setDefaultUnit($defaultUnit)
    {
        $this->defaultUnit = $defaultUnit;
    }

    /**
     * @return int
     */
    public function getDecimalPrecision()
    {
        return $this->decimalPrecision;
    }

    /**
     * @param int $decimalPrecision
     */
    public function setDecimalPrecision($decimalPrecision)
    {
        $this->decimalPrecision = $decimalPrecision;
    }

    /**
     * @return bool
     */
    public function isAutoConvert(): bool
    {
        return $this->autoConvert;
    }

    /**
     * @param bool $autoConvert
     */
    public function setAutoConvert($autoConvert)
    {
        $this->autoConvert = (bool)$autoConvert;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param Model\DataObject\Data\QuantityValue $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\QuantityValue) {
            return [
                $this->getName() . '__value' => $data->getValue(),
                $this->getName() . '__unit' => $data->getUnitId()
            ];
        }

        return [
            $this->getName() . '__value' => null,
            $this->getName() . '__unit' => null
        ];
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param array $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\QuantityValue|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if ($data[$this->getName() . '__value'] || $data[$this->getName() . '__unit']) {
            $quantityValue = new Model\DataObject\Data\QuantityValue($data[$this->getName() . '__value'], $data[$this->getName() . '__unit']);

            if (isset($params['owner'])) {
                $quantityValue->setOwner($params['owner'], $params['fieldname'], $params['language']);
            }

            return $quantityValue;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param Model\DataObject\Data\QuantityValue $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data instanceof Model\DataObject\Data\QuantityValue) {
            return [
                'value' => $data->getValue(),
                'unit' => $data->getUnitId()
            ];
        }

        return null;
    }

    /**
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\QuantityValue|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ($data['value'] || $data['unit']) {
            if (is_numeric($data['unit'])) {
                if ($data['unit'] == -1 || $data['unit'] == null || empty($data['unit'])) {
                    return new Model\DataObject\Data\QuantityValue($data['value'], null);
                }

                return new Model\DataObject\Data\QuantityValue($data['value'], $data['unit']);
            }
        }

        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param float $data
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
            $unit = '';
            if ($data->getUnitId()) {
                $unitDefinition = Model\DataObject\QuantityValue\Unit::getById($data->getUnitId());
                if ($unitDefinition) {
                    $unit = ' ' . $unitDefinition->getAbbreviation();
                }
            }

            return $data->getValue() . $unit;
        }

        return '';
    }

    /**
     * Checks if data is valid for current data field
     *
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     *
     * @throws \Exception
     */
    public function checkValidity($data, $omitMandatoryCheck = false)
    {
        if ($omitMandatoryCheck) {
            return;
        }

        if ($this->getMandatory() &&
            ($data === null || $data->getValue() === null || $data->getUnitId() === null)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!empty($data)) {
            $value = $data->getValue();
            if ((!empty($value) && !is_numeric($data->getValue()))) {
                throw new Model\Element\ValidationException('Invalid dimension unit data ' . $this->getName());
            }

            if (!empty($data->getUnitId())) {
                if (!is_numeric($data->getUnitId())) {
                    throw new Model\Element\ValidationException('Unit id has to be empty or numeric ' . $data->getUnitId());
                }
            }
        }
    }

    /**
     * converts object data to a simple string value or CSV Export
     *
     * @abstract
     *
     * @param Model\DataObject\AbstractObject $object
     * @param array $params
     *
     * @return string
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if ($data instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
            return $data->getValue() . '_' . $data->getUnitId();
        } else {
            return null;
        }
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @param string $importValue
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return float
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        $values = explode('_', $importValue);

        $value = null;
        if ($values[0] && $values[1]) {
            $number = (float) str_replace(',', '.', $values[0]);
            $value = new  \Pimcore\Model\DataObject\Data\QuantityValue($number, $values[1]);
        }

        return $value;
    }

    /**
     * display the quantity value field data in the grid
     *
     * @param $data
     * @param null $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        if ($data instanceof  \Pimcore\Model\DataObject\Data\QuantityValue) {
            $unit = $data->getUnit();
            $unitAbbreviation = '';

            if ($unit instanceof Model\DataObject\QuantityValue\Unit) {
                $unitAbbreviation = $unit->getAbbreviation();
            }

            return [
                'value' => $data->getValue(),
                'unit' => $unit ? $unit->getId() : null,
                'unitAbbr' => $unitAbbreviation
            ];
        }

        return;
    }

    /**
     * converts data to be exposed via webservices
     *
     * @param string $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);

        if ($data instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
            return [
                'value' => $data->getValue(),
                'unit' => $data->getUnitId(),
                'unitAbbreviation' => is_object($data->getUnit()) ? $data->getUnit()->getAbbreviation() : ''
            ];
        } else {
            return null;
        }
    }

    /**
     * converts data to be imported via webservices
     *
     * @param mixed $value
     * @param null|Model\DataObject\AbstractObject $object
     * @param mixed $params
     * @param $idMapper
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($value, $object = null, $params = [], $idMapper = null)
    {
        if (empty($value)) {
            return null;
        } else {
            $value = (array) $value;
            if (array_key_exists('value', $value) && array_key_exists('unit', $value) && array_key_exists('unitAbbreviation', $value)) {
                $unitId = $value['unit'];
                if ($idMapper) {
                    $unitId = $idMapper->getMappedId('unit', $unitId);
                }

                $unit = Model\DataObject\QuantityValue\Unit::getById($unitId);
                if ($unit && $unit->getAbbreviation() == $value['unitAbbreviation']) {
                    return new \Pimcore\Model\DataObject\Data\QuantityValue($value['value'], $unitId);
                } elseif (!$unit && is_null($value['unit'])) {
                    return new \Pimcore\Model\DataObject\Data\QuantityValue($value['value']);
                } else {
                    throw new \Exception(get_class($this).': cannot get values from web service import - unit id and unit abbreviation do not match with local database');
                }
            } else {
                throw new \Exception(get_class($this).': cannot get values from web service import - invalid data');
            }
        }
    }

    /** Encode value for packing it into a single column.
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function marshal($value, $object = null, $params = [])
    {
        if ($params['blockmode'] && $value instanceof Model\DataObject\Data\QuantityValue) {
            return [
                'value' => $value->getValue(),
                'value2' => $value->getUnitId()
            ];
        } elseif ($params['simple']) {
            if (is_array($value)) {
                return [$value[$this->getName() . '__value'], $value[$this->getName() . '__unit']];
            } else {
                return null;
            }
        } else {
            if (is_array($value)) {
                return [
                    'value' => $value[$this->getName() . '__value'],
                    'value2' => $value[$this->getName() . '__unit']
                ];
            } else {
                return [
                    'value' => null,
                    'value2' => null
                ];
            }
        }
    }

    /** See marshal
     * @param mixed $value
     * @param Model\DataObject\AbstractObject $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function unmarshal($value, $object = null, $params = [])
    {
        if ($params['blockmode'] && is_array($value)) {
            return new Model\DataObject\Data\QuantityValue($value['value'], $value['value2']);
        } elseif ($params['simple']) {
            return $value;
        } elseif (is_array($value)) {
            return [
                $this->getName() . '__value' => $value['value'],
                $this->getName() . '__unit' => $value['value2'],

            ];
        } else {
            return null;
        }
    }

    public function configureOptions()
    {
        if (!$this->validUnits) {
            try {
                $table = null;

                if (Runtime::isRegistered(Model\DataObject\QuantityValue\Unit::CACHE_KEY)) {
                    $table = Runtime::get(Model\DataObject\QuantityValue\Unit::CACHE_KEY);
                }

                if (!is_array($table)) {
                    $table = Cache::load(Model\DataObject\QuantityValue\Unit::CACHE_KEY);
                    if (is_array($table)) {
                        Runtime::set(Model\DataObject\QuantityValue\Unit::CACHE_KEY, $table);
                    }
                }

                if (!is_array($table)) {
                    $table = [];
                    $list = new Model\DataObject\QuantityValue\Unit\Listing();
                    $list->setOrderKey('abbreviation');
                    $list->setOrder('asc');
                    $list = $list->load();
                    /** @var $item Model\DataObject\QuantityValue\Unit */
                    foreach ($list as $item) {
                        $table[$item->getId()] = $item;
                    }

                    Cache::save($table, Model\DataObject\QuantityValue\Unit::CACHE_KEY, [], null, 995, true);
                    Runtime::set(Model\DataObject\QuantityValue\Unit::CACHE_KEY, $table);
                }
            } catch (\Exception $e) {
                Logger::error($e);
            }

            if (is_array($table)) {
                $this->validUnits = [];
                /** @var $unit Model\DataObject\QuantityValue\Unit */
                foreach ($table as $unit) {
                    $this->validUnits[] = $unit->getId();
                }
            }
        }
    }

    /**
     * @param $data
     *
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = parent::__set_state($data);
        $obj->configureOptions();

        return $obj;
    }
}
