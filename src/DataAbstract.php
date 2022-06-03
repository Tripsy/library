<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 */

namespace Tripsy\Library;

use ReflectionClass;
use Tripsy\Library\Exceptions\ConfigException;

abstract class DataAbstract
{
    /**
     * List with declared properties [name => type]
     *
     * @var array
     */
    private array $property_list = [];

    /**
     * Checks if the declared property's accessibility is protected
     * Checks if the declared property's type is set
     *
     * @throws ConfigException
     */
    public function __construct()
    {
        $reflectionClass = new ReflectionClass($this);

        $property_list = $reflectionClass->getProperties();

        foreach ($property_list as $property) {
            if ($property->isProtected() === false) {
                throw new ConfigException('Property accessibility is not marked as protected (eg: ' . $property->getName() . ')');
            }

            if ($property->getType() === null) {
                throw new ConfigException('Property type is not set (eg: ' . $property->getName() . ')');
            }

            $this->property_list[$property->getName()] = $property->getType()->getName();
        }
    }

    /**
     * Check if property is declared
     * Initialize the property
     *
     * @param string $property
     * @return void
     * @throws ConfigException
     */
    private function checkPropertyExist(string $property): void
    {
        if (array_key_exists($property, $this->property_list) === false) {
            throw new ConfigException('Property is not declared (eg: ' . $property . ')');
        }

        //initialize property
        if (isset($this->$property) === false) {
            $property_type = $this->property_list[$property];

            switch ($property_type) {
                case 'string':
                    $this->$property = '';
                    break;
                case 'array':
                    $this->$property = [];
                    break;
                case 'int':
                    $this->$property = 0;
                    break;
                case 'float':
                    $this->$property = 0.00;
                    break;
                default:
                    throw new ConfigException('Property type not supported (eg: ' . $property . ')');
            }
        }
    }

    /**
     * Get property type name
     *
     * @param string $property
     * @return string
     */
    private function getPropertyType(string $property): string
    {
        return $this->property_list[$property];
    }

    /**
     * Return property value
     *
     * @param string $property
     * @return mixed
     * @throws ConfigException
     */
    public function get(string $property): mixed
    {
        $this->checkPropertyExist($property);

        return $this->$property;
    }

    /**
     * Set property value
     *
     * @param string $property
     * @param $value
     * @return void
     * @throws ConfigException
     */
    public function set(string $property, $value)
    {
        $this->checkPropertyExist($property);

        $this->$property = $value;
    }

    /**
     * If property is string append $value
     *
     * @param string $property
     * @param $value
     * @return void
     * @throws ConfigException
     */
    public function concatenate(string $property, $value)
    {
        $this->checkPropertyExist($property);

        if ($this->getPropertyType($property) == 'string') {
            $this->$property .= $value;
        } else {
            throw new ConfigException('Property is not string (eg: ' . $property . ')');
        }
    }

    /**
     * If property is array push pair [key => value]
     *
     * @param string $property
     * @param $value
     * @param string $key
     * @return void
     * @throws ConfigException
     */
    public function push(string $property, $value, string $key = '')
    {
        $this->checkPropertyExist($property);

        if ($this->getPropertyType($property) == 'array') {
            if ($key) {
                ($this->$property)[$key] = $value;
            } else {
                $this->$property[] = $value;
            }
        } else {
            throw new ConfigException('Property is not array (eg: ' . $property . ')');
        }
    }

    /**
     * Merge property with values
     *
     * @param string $property
     * @param array $values
     * @return void
     * @throws ConfigException
     */
    public function merge(string $property, array $values)
    {
        $this->checkPropertyExist($property);

        if ($this->getPropertyType($property) == 'array') {
            $this->$property = array_merge($this->$property, $values);
        } else {
            throw new ConfigException('Property is not array (eg: ' . $property . ')');
        }
    }

    /**
     * Return array with properties [key => value]
     *
     * @return array
     * @throws ConfigException
     */
    public function list(): array
    {
        $data = [];

        $property_list = array_keys($this->property_list);

        foreach ($property_list as $name) {
            $data[$name] = $this->get($name);
        }

        return $data;
    }

    /**
     * Reset properties
     *
     * @return void
     * @throws ConfigException
     */
    public function reset()
    {
        foreach ($this->property_list as $name => $type) {
            switch ($type) {
                case 'string':
                    $this->set($name, '');
                    break;
                case 'array':
                    $this->set($name, []);
                    break;
                case 'int':
                    $this->set($name, 0);
                    break;
                case 'float':
                    $this->set($name, 0.00);
                    break;
            }
        }
    }

    /**
     * Load properties values from array
     *
     * @param array $data
     * @return void
     * @throws ConfigException
     */
    public function load(array $data): void
    {
        $property_list = array_intersect_key($data, $this->property_list);

        foreach ($property_list as $name => $value) {
            $this->set($name, $value);
        }
    }
}
