<?php
/**
 * Copyright © 2014, REZO ZERO
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file SerializerInterface.php
 * @copyright REZO ZERO 2014
 * @author Thomas Aufresne
 */
namespace RZ\Renzo\Core\Serializers;

/**
 * EntitySerializer that implements simple serialization/deserialization methods.
 */
interface SerializerInterface
{

    /**
     * Serializes data.
     *
     * @param mixed $obj
     *
     * @return mixed
     */
    public static function serialize($obj);


    /**
     * Create a simple associative array with an entity.
     *
     * @param mixed $obj
     *
     * @return array
     */
    public static function toArray($obj);

    /**
     * Deserializes a json file into a readable array of datas.
     *
     * @param string $string Input to deserialize
     *
     * @return mixed
     */
    public static function deserialize($string);
}
