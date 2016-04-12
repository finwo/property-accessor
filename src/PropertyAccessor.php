<?php

namespace Finwo\PropertyAccessor;

class PropertyAccessor
{
    protected $debug = false;

    public function __construct($debug = false)
    {
        $this->debug          = $debug;
    }

    /**
     * @param        $subject
     * @param string $path
     * @param string $pathSplit
     *
     * @return array|mixed|null
     * @throws \Exception
     */
    public function get($subject, $path = '', $pathSplit = '|')
    {
        // try array for legacy mapper
        if (is_array($subject)) {
            $path = explode($pathSplit, $path);
            return $this->getArrayProperty($subject, $path);
        }

        // throw error if needed
        if (!is_object($subject)) {
            throw new \Exception(sprintf(
                'Subject must be an array or object, %s given',
                gettype($subject)
            ));
        }

        $camelized = $this->camelize($path);

        // try the default getter
        if (method_exists($subject, $method = sprintf("get%s", $camelized))) {
            return call_user_func(array(
                $subject,
                $method
            ));
        }

        // try less-common getter
        if (method_exists($subject, $method = lcfirst($camelized))) {
            return call_user_func(array(
                $subject,
                $method
            ));
        }

        // try generic getter
        if (method_exists($subject, 'get')) {
            return $subject->get($path);
        }

        // try generic hidden getter
        if (method_exists($subject, '__get')) {
            return $subject->__get($path);
        }

        // try fetching directly
        $rc = new \ReflectionObject($subject);
        foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getName() == $path) {
                return $subject->{$path};
            }
        }

        // all methods failed, throw exception
        if ($this->getDebug()) {
            return '#ERROR';
        }
        throw new \Exception(sprintf(
            'Required property "%s" of class %s is missing in data',
            '',
            ''
        ));
    }

    /**
     * @param        $subject
     * @param string $path
     * @param        $value
     * @param string $pathSplit
     *
     * @return PropertyAccessor
     * @throws \Exception
     */
    public function set(&$subject, $path = '', $value, $pathSplit = '|')
    {
        // try array for legacy mapper
        if (is_array($subject)) {
            $path = explode($pathSplit, $path);
            return $this->setArrayProperty($subject, $path, $value);
        }

        // throw error if needed
        if (!is_object($subject)) {
            throw new \Exception(sprintf(
                'Subject must be an array or object, %s given',
                gettype($subject)
            ));
        }

        // all methods failed, throw exception
        if ($this->getDebug()) {
            return '#ERROR';
        }

        throw new \Exception(sprintf(
            'Required property "%s" of class %s is missing in data',
            '',
            ''
        ));
    }

    protected function getArrayProperty($input = array(), $path = array())
    {
        $target = $input;
        foreach($path as $key) {
            if(isset($target[$key])) {
                $target = $target[$key];
            } else {
                return null;
            }
        }
        return $target;
    }

    protected function setArrayProperty(&$input = array(), $path = array(), $value)
    {
        $target = &$input;
        foreach($path as $key) {
            if(!isset($target[$key])) $target[$key] = array();
            $target = &$target[$key];
        }
        $target = $value;

        return $this;
    }

    /**
     * Simple camelize function
     *
     * @param string $scored
     * @return mixed
     */
    protected function camelize( $scored = '' ) {
        $output = str_replace(array('-', '_'), " ", $scored );
        return str_replace(' ','',ucwords($output));
    }

    protected function getDebug()
    {
        return $this->debug;
    }
}