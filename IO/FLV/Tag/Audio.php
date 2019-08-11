<?php

/*
  IO_FLV_Tag_Audio class
  (c) 2019/08/11 yoya@awm.jp
 */

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/Bit.php';
}

class IO_FLV_Tag_Audio {
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
    function parse($bitin, $dataSize) {
        list($startOffset, $dummy) = $bitin->getOffset();
        $this->SoundFormat = $bitin->getUIBits(4);
        $this->SoundRate = $bitin->getUIBits(2);
        $this->SoundSize = $bitin->getUIBit();
        $this->SoundType = $bitin->getUIBit();
        if ($this->SoundFormat == 10) {
            $this->AACPacketType = $bitin->getUI8();
        }
        list($bodyStartOffset, $dummy) = $bitin->getOffset();
        $this->SoundData = $bitin->getData($dataSize - ($bodyStartOffset - $startOffset));
    }
    function dump() {
        echo "SoundFormat:".$this->SoundFormat;
        echo "(".self::getAudioFormatName($this->SoundFormat).") ";
        echo "SoundRate:".$this->SoundRate;
        echo "(".self::getAudioRateName($this->SoundRate).")".PHP_EOL;
        echo "SoundSize:".$this->SoundSize;
        echo "(".self::getAudioSizeName($this->SoundSize).") ";
        echo "SoundType:".$this->SoundType;
        echo "(".self::getAudioTypeName($this->SoundType).")".PHP_EOL;
        if (isset($this->AACPacketType)) {
            echo "AACPacketType:".$this->AACPacketType.PHP_EOL;
        }
        $bit = new IO_Bit();
        $bit->input($this->SoundData);
        $bit->hexdump(0, 0x10);
    }
}
