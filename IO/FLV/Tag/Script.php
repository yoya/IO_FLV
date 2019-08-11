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
        echo "Type:$Type\n";
        switch ($Type) {
        case 0: // Double
            // $Value = $bitin->getUI32BE();
            $Value = $bitin->getData(8);
            break;
        case 2: // String
            $length = $bitin->getUI16BE();
            echo "string length:$length\n";
            $Value = $bitin->getData($length);
            break;
        case 8: // ECMA array
            $length = $bitin->getUI32BE();
            echo "ECMA length:$length\n";
            $Variables = [];
            for ($i = 0 ; $i < $length ; $i++) {
                $Variables []= $this->parseScriptDataValue($bitin);
            }
            $Value = $Variables;
            break;
        default:
            exit (1);
            break;
        }
        echo "Value:$Value\n";
        return ["Type" => $Type, "Value" => $Value];
    }
    function dump() {
        echo "FrameType:".$this->FrameType;
        echo "(".self::getVideoFrameTypeName($this->FrameType).") ";
        echo "CodecID:".$this->CodecID;
        echo "(".self::getVideoCodecIDName($this->CodecID).")".PHP_EOL;
        if (isset($this->AVCPacketType) || isset($this->CompositionTime)) {
            if (isset($this->AVCPacketType)) {
                    echo "AVCPacketType:".$this->AVCPacketType." ";
            }
            if (isset($this->CompositionTime)) {
                echo "CompositionType:".$this->CompositionType;
            }
            echo PHP_EOL;
        }
        $bit = new IO_Bit();
        $bit->input($this->Data);
        $bit->hexdump(0, 0x10);
    }
}
