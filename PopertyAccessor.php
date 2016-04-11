<?php

namespace Finwo\PropertyAccessor;

class PropertyAccessor
{
    public function get($subject, $path = '', $pathSplit = '|')
    {
        //split the path
        $path = explode($pathSplit, $path);

        //try array
        if (is_array($subject)) {
            return $this->getArrayProperty($subject, $path);
        }

        //all methods failed, throw exception
        throw new \Exception(sprintf(
            'Required property "%s" of class %s is missing in data',
            '',
            ''
        ));
    }

    public function set($subject, $path = '', $value, $pathSplit = '|')
    {
        //split the path
        $path = explode($pathSplit, $path);

        //try array
        if (is_array($subject)) {
            return $this->setArrayProperty($subject, $path, $value);
        }

        //all methods failed, throw exception
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
}