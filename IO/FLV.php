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
    static function getAudioFormatName($format) {
        static $formatNameTable = [
            0 => "LinearPCM(platform endian)",
            1 => "ADPCM",
            2 => "MP3",
            3 => "LinearPCM(little endian)",
            4 => "Nellymoser 16 Hz mono",
            5 => "Nellymoser 18Hz mono",
            6 => "Nellymoser",
            7 => "G.711 A-law logarithmic PCM",
            8 => "G.711 mu-law logarithmic PCM",
            9 => "reserved",
            10 => "AAC",
            11 => "Speex",
            14 => "MP3 8kHz",
            15 => "Deviice-specific sound",
        ];
        if (isset($formatNameTable[$format])) {
            return $formatNameTable[$format];
        }
        return "UnknownFormnat";
    }
    static function getAudioRateName($rate) {
        static $rateNameTable = [
            0 => "5.5 kHz",
            1 => "11 kHz",
            2 => "22 kHz",
            3 => "44 kHz",
        ];
        if (isset($rateNameTable[$rate])) {
            return $rateNameTable[$rate];
        }
        return "UnknownRate";
    }        
    static function getAudioSizeName($size) {
        static $sizeNameTable = [
            0 => "8-bit samples",
            1 => "16-bit samples",
        ];
        if (isset($sizeNameTable[$size])) {
            return $sizeNameTable[$size];
        }
        return "UnknownSize";
    }
    static function getAudioTypeName($type) {
        static $typeNameTable = [
            0 => "Mono sound",
            1 => "Stereo sound",
        ];
        if (isset($typeNameTable[$type])) {
            return $typeNameTable[$type];
        }
        return "UnknownType";
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
                printf(STDERR, $e);
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
            $tag['AudioTagHeader'] = $this->parseTagAudioHeader($bitin, $dataSize);
            list($bodyStartOffset, $dummy) = $bitin->getOffset();
            $tag['AudioTagBody'] = $bitin->getData($DataSize - ($bodyStartOffset - $dataStartOffset));
            break;
        case 9: // Video Tag
            break;
        case 18: // Script Tag
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
    function parseTagAudioHeader($bitin, $dataSize) {
        $SoundFormat = $bitin->getUIBits(4);
        $SoundRate = $bitin->getUIBits(2);
        $SoundSize = $bitin->getUIBit();
        $SoundType = $bitin->getUIBit();
        $header = [
            'SoundFormat' => $SoundFormat,
            'SoundRate' =>$SoundRate,
            'SoundSize' =>$SoundSize,
            'SoundType' =>$SoundType,
        ];
        if ($SoundFormat == 10) {
            $header['AACPacketType'] = $bitin->getUI8();
        }
        return $header;
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
                $this->dumpTagAudioHeader($tag['AudioTagHeader']);
                $bit = new IO_Bit();
                $bit->input($tag['AudioTagBody']);
                $bit->hexdump(0, 0x10);
                break;
            case 9: // Video Tag
                break;
            case 18: // Script Tag
                break;
            default:
                break;
            }
        }
    }
    function dumpTagAudioHeader($header) {
        echo "SoundFormat:".$header["SoundFormat"];
        echo "(".self::getAudioFormatName($header["SoundFormat"]).") ";
        echo "SoundRate:".$header["SoundRate"];
        echo "(".self::getAudioRateName($header["SoundRate"]).")".PHP_EOL;
        echo "SoundSize:".$header["SoundSize"];
        echo "(".self::getAudioSizeName($header["SoundSize"]).") ";
        echo "SoundType:".$header["SoundType"];
        echo "(".self::getAudioTypeName($header["SoundType"]).")".PHP_EOL;
        if (isset($header['AACPacketType'])) {
            echo "AACPacketType:".$audioHeader['AACPacketType'].PHP_EOL;
        }
    }
}
