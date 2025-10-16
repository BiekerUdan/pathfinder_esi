<?php
/**
 * Abstract Iterator for mapping API responses
 * Copied from main Pathfinder application for standalone library testing
 */

namespace Exodus4D\Pathfinder\Data\Mapper;

class AbstractIterator extends \RecursiveArrayIterator {

    /**
     * iterator mapping
     * -> overwrite in child classes (late static binding)
     * @var array
     */
    protected static $map = [];

    /**
     * remove unmapped values from Array
     * -> see $map
     * @var bool
     */
    protected static $removeUnmapped = true;

    /**
     * AbstractIterator constructor.
     * @param $data
     */
    function __construct($data){
        // Convert stdClass objects to arrays
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }
        parent::__construct($data, \RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * map iterator
     * @return array
     */
    public function getData(){
        iterator_apply($this, 'self::recursiveIterator', [$this]);

        return iterator_to_array($this, true);
    }

    /**
     * convert array keys to camelCase
     * @param $array
     * @return array
     */
    protected function camelCaseKeys($array){
        return $this->arrayChangeKeys($array, function($key) {
            return \Base::instance()->camelcase($key);
        });
    }

    /**
     * Change array keys using callback
     * @param array $array
     * @param callable $callback
     * @return array
     */
    private function arrayChangeKeys(array $array, callable $callback): array {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$callback($key)] = $value;
        }
        return $result;
    }

    /**
     * Check if array is associative
     * @param mixed $data
     * @return bool
     */
    private function is_assoc($data): bool {
        if (is_object($data)) {
            $data = (array)$data;
        }
        if (!is_array($data) || [] === $data) return false;
        return array_keys($data) !== range(0, count($data) - 1);
    }

    /**
     * recursive iterator function called on every node
     * @param AbstractIterator $iterator
     * @return AbstractIterator
     */
    static function recursiveIterator(AbstractIterator $iterator){

        $keyWhitelist = array_keys(static::$map);

        while($iterator->valid()){

            if( isset(static::$map[$iterator->key()]) ){
                $mapValue = static::$map[$iterator->key()];

                // check for mapping key
                if(
                    $iterator->hasChildren() &&
                    $iterator->is_assoc($iterator->current())
                ){
                    // recursive call for child elements
                    $iterator->offsetSet($iterator->key(), forward_static_call(array('self', __METHOD__), $iterator->getChildren())->getArrayCopy());
                    $iterator->next();
                }elseif(is_array($mapValue)){
                    // a -> array mapping
                    $parentKey = array_keys($mapValue)[0];
                    $entryKey = array_values($mapValue)[0];

                    // check if key already exists
                    if($iterator->offsetExists($parentKey)){
                        $currentValue = $iterator->offsetGet($parentKey);
                        // add new array entry
                        $currentValue[$entryKey] = $iterator->current();
                        $iterator->offsetSet($parentKey, $currentValue);
                    }else{
                        $iterator->offsetSet($parentKey, [$entryKey => $iterator->current()]);
                        $keyWhitelist[] = $parentKey;
                    }

                    $iterator->offsetUnset($iterator->key());
                }elseif(is_object($mapValue)){
                    // a -> a (format by function)
                    $formatFunction = $mapValue;
                    $iterator->offsetSet($iterator->key(), call_user_func($formatFunction, $iterator));

                    // just value change no key change
                    $iterator->next();
                }elseif($mapValue !== $iterator->key()){
                    // a -> b mapping (key changed)
                    $iterator->offsetSet($mapValue, $iterator->current());
                    $iterator->offsetUnset($iterator->key());
                    $keyWhitelist[] = $mapValue;
                }else{
                    // a -> a (no changes)
                    $iterator->next();
                }

            }elseif(
                static::$removeUnmapped &&
                !in_array($iterator->key(), $keyWhitelist)
            ){
                $iterator->offsetUnset($iterator->key());
            }else{
                $iterator->next();
            }

        }

        return $iterator;
    }

}
