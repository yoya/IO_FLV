<?php

/*
  IO_FLV_Tag_Script class
  (c) 2019/08/11 yoya@awm.jp
 */

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/Bit.php';
}

class IO_FLV_Tag_Script {
    static function getScriptTypeName($type) {
        static $scriptTypeNameTable = [
            0 => "Number",
            1 => "Boolean",
            2 => "String",
            3 => "Object",
            4 => "MovieClip",  // reserved, not supported
            5 => "Null",
            6 => "Undefined",
            7 => "Reference",
            8 => "ECMA array",
            9 => "Object end marker",
            10 => "Strict array",
            11 => "Date",
            12 => "Long string",

        ];
        if (isset($scriptTypeNameTable[$type])) {
            return $scriptTypeNameTable[$type];
        }
        return "UnknownCodecID";
    }

    function parse($bitin, $dataSize) {
        // list($startOffset, $dummy) = $bitin->getOffset();
        // list($offset, $dummy) = $bitin->getOffset();
        $this->Name = $this->parseScriptDataValue($bitin);
        $this->Value = $this->parseScriptDataValue($bitin);
    }

    function parseScriptDataValue($bitin) {
        $Type = $bitin->getUI8();
        switch ($Type) {
        case 0: // Double
            $Value = $bitin->getData(8);
            if (unpack('S',"\x01\x00")[1] === 1) { // little endian
                $Value = strrev($Value);
            }
            $Value = unpack("d", $Value)[1];
            break;
        case 1: // Boolean
            $Value = $bitin->getUI8();
            break;
        case 2: // String
            $length = $bitin->getUI16BE();
            $Value = $bitin->getData($length);
            break;
        case 5: // Null
            $Value = null;
            break;
        case 6: // Undefined
            $Value = null;
            break;
        case 7: // Boolean
            $Value = $bitin->getUI16BE();
            break;
        case 8: // ECMA array
            $length = $bitin->getUI32BE();
            $Variables = [];
            for ($i = 0 ; $i < $length ; $i++) {
                $Variables []= $this->parseScriptDataObjectProperty($bitin);
            }
            $Value = $Variables;
            break;
        case 12: // Long string
            $length = $bitin->getUI32BE();
            $Value = $bitin->getData($length);
            break;
        default:
            throw new Exception("Unknown Type:$Type(".self::getScriptTypeName($Type).")");
            break;
        }
        return ["Type" => $Type, "Value" => $Value];
    }
    function parseScriptDataObjectProperty($bitin) {
            $length = $bitin->getUI16BE();
            $PropertyName = $bitin->getData($length);
            $PropertyValue = $this->parseScriptDataValue($bitin);
            return ["Name" =>$PropertyName , "Value" => $PropertyValue];
    }
    function dump() {
        $this->dumpDataValue($this->Name);
        $this->dumpDataValue($this->Value);
    }
    function dumpDataValue($data, $level = 0) {
        if (! isset($data["Type"])) {
            throw new Exception("Internal error: Type undefined");
            return ;
        }
        $Type = $data["Type"];
        $Value = $data["Value"];
        echo str_repeat("  ", $level);
        echo "  Type:$Type(".self::getScriptTypeName($Type). ") ";
        switch ($Type) {
        case 0: // Double
        case 1: // Boolean
        case 2: // String
        case 7: // Reference
        case 12: // Long string
            echo "Value:$Value".PHP_EOL;
            break;
        case 5: // Null
        case 6: // Undefined
            echo PHP_EOL;
            break;
        case 8: // ECMA array
            $arrayLength = count($Value);
            echo "Length:$arrayLength".PHP_EOL;
            foreach ($Value as $nv) {
                $n = $nv["Name"];
                $v = $nv["Value"];
                echo str_repeat("  ", $level);
                echo "  Name:$n: Value:".PHP_EOL;
                $this->dumpDataValue($v, $level+1);
            }
            break;
        default:
            throw new Exception("Unknown Type:$Type(".self::getScriptTypeName($Type).")");
            break;
        }
    }
}
