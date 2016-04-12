<?php

//include composer's autoloader in this example
include __DIR__ . '/vendor/autoload.php';

use Finwo\PropertyAccessor\PropertyAccessor;

class testObject
{
    public $pubVar = 'pubValue';

    protected $proVar = 'proValue';
    protected $proAccessibleVar = 'proAccessibleValue';

    private $priVar = 'priValue';
    private $priAccessibleVar = '';

    /**
     * @return mixed
     */
    public function getProAccessibleVar()
    {
        return $this->proAccessibleVar;
    }

    /**
     * @param $proAccessibleVar
     * @return $this
     */
    public function setProAccessibleVar($proAccessibleVar)
    {
        $this->proAccessibleVar = $proAccessibleVar;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPriAccessibleVar()
    {
        return $this->priAccessibleVar;
    }

    /**
     * @param $priAccessibleVar
     * @return $this
     */
    public function setPriAccessibleVar($priAccessibleVar)
    {
        $this->priAccessibleVar = $priAccessibleVar;
        return $this;
    }

}

$obj = new testObject();
$arr = array(
    'type' => 'person',
    'person' => array(
        'name' => 'John Doe',
        'age'  => '40'
    )
);

//accessor in debug mode
$acc = new PropertyAccessor(true);

printf("\$obj->pubvar           : %s\n", $acc->get($obj, 'pubVar'));
printf("\$obj->proVar           : %s\n", $acc->get($obj, 'proVar'));
printf("\$obj->proAccessibleVar : %s\n", $acc->get($obj, 'proAccessibleVar'));
printf("\$obj->priVar           : %s\n", $acc->get($obj, 'priVar'));
printf("\$obj->priAccessibleVar : %s\n", $acc->get($obj, 'priAccessibleVar'));

print("\n");

printf("\$arr|type        : %s\n", $acc->get($arr, 'type'));
printf("\$arr|person|name : %s\n", $acc->get($arr, 'person|name'));
printf("\$arr|person|age  : %s\n", $acc->get($arr, 'person|age'));
