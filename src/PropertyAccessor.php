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
     * @param null   $default
     * @param array  $options
     *
     * @return array|mixed|null
     * @throws \Exception
     */
    public function getSafe(&$subject, $path = '', $pathSplit = '|', $default = null, $options = array())
    {
        $result = $this->get($subject, $path, $pathSplit);
        if (in_array($result, $options) || is_null($result)) {
            $result = &$default;
        }
        return $result;
    }

    /**
     * @param        $subject
     * @param string $path
     * @param string $pathSplit
     *
     * @return array|mixed|null
     * @throws \Exception
     */
    public function get(&$subject, $path = '', $pathSplit = '|')
    {
        // try array for legacy mapper
        if (is_array($subject)) {
            $path = explode($pathSplit, $path);
            return $this->getArrayProperty($subject, $path, $pathSplit);
        }

        // throw error if needed
        if (!is_object($subject)) {
            throw new \Exception(sprintf(
                'Subject must be an array or object, %s given',
                gettype($subject)
            ));
        }

        // handle in-depth request
        if (strpos($path, $pathSplit)) {
            $target = $subject;
            $path = explode($pathSplit, $path);
            foreach($path as $part) {
                $target = &$this->get($target, $part, $pathSplit);
            }
            return $target;
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

        // try magic getter
        if (method_exists($subject, 'get')) {
            return $subject->get($path);
        }

        // try magic hidden getter
        if (method_exists($subject, '__get')) {
            return $subject->__get($path);
        }

        // try getting directly
        $rc = new \ReflectionObject($subject);
        foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getName() == $path) {
                return $subject->{$path};
            }
        }

        // all methods failed, throw exception
        if ($this->getDebug()) {
            return null;
        }
        throw new \Exception(sprintf(
            'The property "%s" from object of class %s was inaccessible.',
            $path,
            get_class($subject)
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
    public function set(&$subject, $path = '', &$value, $pathSplit = '|')
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

        // handle in-depth request
        if (strpos($path, $pathSplit)) {
            $target = &$subject;
            $path = explode($pathSplit, $path);
            $last = array_pop($path);
            foreach($path as $part) {
                $target = &$this->get($target, $part, $pathSplit);
            }
            return $this->set($target, $last, $value, $pathSplit);
        }

        $camelized = $this->camelize($path);

        // try the default setter
        if (method_exists($subject, $method = sprintf("set%s", $camelized))) {
            call_user_func(array(
                $subject,
                $method
            ),
                $value
            );
            return $this;
        }

        // try less-common setter
        if (method_exists($subject, $method = lcfirst($camelized))) {
            call_user_func(array(
                $subject,
                $method
            ),
                $value
            );
            return $this;
        }

        // try magic getter
        if (method_exists($subject, 'set')) {
            $subject->set($path, $value);
            return $this;
        }

        // try magic hidden setter
        if (method_exists($subject, '__set')) {
            $subject->__set($path, $value);
            return $this;
        }

        // try getting directly
        $rc = new \ReflectionObject($subject);
        foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getName() == $path) {
                $subject->{$path} = &$value;
                return $this;
            }
        }

        // all methods failed, throw exception
        if ($this->getDebug()) {
            return $this;
        }

        throw new \Exception(sprintf(
            'The property "%s" from object of class %s was inaccessible.',
            $path,
            get_class($subject)
        ));
    }

    protected function getArrayProperty($input = array(), $path = array(), $splitChar = '|')
    {
        $target = &$input;
        foreach($path as $key) {
            if(isset($target[$key])) {
                $target = &$target[$key];
            } else {
                if ($this->debug) return null;
                throw new \Exception(sprintf(
                    'The property "%s" from array was inaccessible.',
                    implode($splitChar, $path)
                ));
            }
        }
        return $target;
    }

    public function remove(&$input, $path, $splitChar = '|')
    {
        // Do nothing if we can't use the property
        if (!is_array($input) && !is_object($input)) {
            return;
        }

        // Convert the path to something useful
        if (!is_array($path)) {

            // Do nothing if we can't use the path
            if (!is_string($path)) {
                return;
            }

            // Split by the split character
            $path = explode($splitChar, $path);
        }

        // Fetch the last key
        $lastKey = array_pop($path);

        // Fetch the parent
        if (count($path)) {
            $target = &$this->get($input, implode('|', $path), '|');
        } else {
            $target = &$input;
        }

        // Handle if the target is an array
        if (is_array($target)) {
            if (!isset($target[$lastKey])) {
                return;
            }
            unset($target[$lastKey]);
            return;
        }

        // Handle if the target is an object
        if (is_object($target)) {
            if (!isset($target->{$lastKey})) {
                return;
            }
            unset($target->{$lastKey});
            return;
        }

        // We're not compatible with this type
        return;
    }

    protected function setArrayProperty(&$input = array(), $path = array(), &$value)
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

    public function mergeArrays(&$original, $new)
    {
        $source = &$original;

        foreach($new as $key => $value) {
            if(is_array($value)) {
                // nest deeper
                if(!isset($source[$key])) {
                    $source[$key] = array();
                }
                $this->mergeArrays($source[$key], $value);
            } else {
                // overwrite
                $source[$key] = $value;
            }
        }
    }

    protected function getDebug()
    {
        return $this->debug;
    }
}
