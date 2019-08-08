<?php

/*
  IO_FLV class
  (c) 2019/08/08 yoya@awm.jp
  ref) http://pwiki.awm.jp/~yoya/?FLV
 */

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/Bit.php';
}

class IO_FLV {
    static function getTagName($tagType) {
        static $tagNameTable = [
            8 => "AudioTag",
            9 => "VideoTag",
            18 => "ScriptTag",
        ];
        if (isset($tagNameTable[$tagType])) {
            return $tagNameTable[$tagType];
        }
        return "UnknownTag";
    }        
    var $_flvdata = null;
    function parse($flvdata) {
        $this->_flvdata = $flvdata;
        $bitin = new IO_Bit();
        $bitin->input($flvdata);
        $this->Signature = $bitin->getData(3);
        $this->Version = $bitin->getUI8();
        $this->TypeFlags = $bitin->getUI8();
        $this->TypeFlagsAudio = ($this->TypeFlags & 4)?true:false;
        $this->TypeFlagsVideo = ($this->TypeFlags & 1)?true:false;
        $this->DataOffset = $bitin->getUI32BE();
        $bitin->setOffset($this->DataOffset, 0);
        $tagList = [];
        while ($bitin->hasNextData(8)) {
            try {
                $tagList []= $this->parseTag($bitin);
            } catch (Exception $e) {
                printf(STDERR, $e);
                break;
            }
        }
        $this->TagList =  $tagList;
    }
    function parseTag($bitin) {
        $PreviousTagSize = $bitin->getUI32BE();
        $Reserved = $bitin->getUIBits(2);
        $Filter = $bitin->getUIBit();
        $TagType = $bitin->getUIBits(5);
        $DataSize = $bitin->getUIBits(24);
        $Timestamp = $bitin->getUIBits(24);
        $TimestampExtended = $bitin->getUI8();
        // $bitin->hexdump($bitin->getOffset()[0], 10);
        $StreamID = $bitin->getUIBits(24);
        $bitin->getData($DataSize);
        return ['PreviousTagSize' => $PreviousTagSize,
                'Filter' => $Filter, 'TagType' => $TagType,
                'DataSize' => $DataSize,
                'Timestamp' => $Timestamp,
                'TimestampExtended' => $TimestampExtended,
                'StreamID' => $StreamID];
    }
    function dump() {
        echo "Signature:".$this->Signature." ";
        echo "Version:".$this->Version." ";
        echo "Audio:".$this->TypeFlagsAudio." ";
        echo "Video:".$this->TypeFlagsVideo." ";
        echo "DataOffset:".$this->DataOffset.PHP_EOL;
        foreach ($this->TagList as $idx => $tag) {
            echo "[$idx] ";
            echo "PreviousTagSize:".$tag["PreviousTagSize"]." ";
            echo "Filter:".$tag["Filter"].PHP_EOL;
            echo "    TagType:".$tag["TagType"];
            echo "(".self::getTagName($tag["TagType"]).") ";
            echo "DataSize:".$tag["DataSize"]." ";
            echo "Timestamp:".$tag["Timestamp"]." ";
            if ($tag["TimestampExtended"] > 0) {
                echo "TimestampExtended:".$tag["TimestampExtended"]." ";
            }
            echo "StreamID:".$tag["StreamID"];
            echo PHP_EOL;
        }
    }
}
