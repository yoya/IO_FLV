<?php

/*
  IO_FLV class
  (c) 2019/08/08 yoya@awm.jp
  ref) https://www.adobe.com/devnet/f4v.html
 */

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/Bit.php';
    require_once 'IO/FLV/Tag/Audio.php';
    require_once 'IO/FLV/Tag/Video.php';
    require_once 'IO/FLV/Tag/Script.php';
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
        if ($this->Signature !== "FLV") {
            throw new Exception ("Not FLV FILE (bad signature)");
        }
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
                fprintf(STDERR, $e);
                break;
            }
        }
        $this->TagList =  $tagList;
    }
    function parseTag($bitin) {
        $PreviousTagSize = $bitin->getUI32BE();
        $Reserved = $bitin->getUIBits(2);  // xx......
        $Filter = $bitin->getUIBit();      // ..x.....
        $TagType = $bitin->getUIBits(5);   // ...xxxxx
        $DataSize = $bitin->getUIBits(24);
        $Timestamp = $bitin->getUIBits(24);
        $TimestampExtended = $bitin->getUI8();
        $StreamID = $bitin->getUIBits(24);
        $tag = ['PreviousTagSize' => $PreviousTagSize,
                'Filter' => $Filter, 'TagType' => $TagType,
                'DataSize' => $DataSize,
                'Timestamp' => $Timestamp,
                'TimestampExtended' => $TimestampExtended,
                'StreamID' => $StreamID];
        list($dataStartOffset, $dummy) = $bitin->getOffset();
        switch ($TagType) {
        case 8: // Audio Tag
            $audioTag = new IO_FLV_Tag_Audio();
            $audioTag->parse($bitin, $DataSize);
            $tag['AudioTag'] = $audioTag;
            break;
        case 9: // Video Tag
            $videoTag = new IO_FLV_Tag_Video();
            $videoTag->parse($bitin, $DataSize);
            $tag['VideoTag'] = $videoTag;
            break;
        case 18: // Script Tag
            $scriptTag = new IO_FLV_Tag_Script();
            $scriptTag->parse($bitin, $DataSize);
            $tag['ScriptTag'] = $scriptTag;
            break;
        default:
            break;
        }
        if ($Filter > 0) {
            throw new Exception("Filter:$Filter not implemented yet.");
        }
        $bitin->setOffset($dataStartOffset + $DataSize, 0);
        return $tag;
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
            echo "Filter:".$tag["Filter"]." ";
            echo "TagType:".$tag["TagType"];
            echo "(".self::getTagName($tag["TagType"]).")".PHP_EOL;
            echo "  DataSize:".$tag["DataSize"]." ";
            echo "Timestamp:".$tag["Timestamp"]." ";
            if ($tag["TimestampExtended"] > 0) {
                echo "TimestampExtended:".$tag["TimestampExtended"]." ";
            }
            echo "StreamID:".$tag["StreamID"];
            echo PHP_EOL;
            switch ($tag["TagType"]) {
            case 8: // Audio Tag
                $tag['AudioTag']->dump();
                break;
            case 9: // Video Tag
                $tag['VideoTag']->dump();
                break;
            case 18: // Script Tag
                $tag['ScriptTag']->dump();
                break;
            default:
                break;
            }
        }
    }
}
