<?php

/*
  IO_FLV_Tag_Video class
  (c) 2019/08/11 yoya@awm.jp
 */

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/Bit.php';
}

class IO_FLV_Tag_Video {
    static function getVideoFrameTypeName($type) {
        static $frameTypeNameTable = [
            1 => "key frame (AVC, seekable)",
            2 => "inter frame (AVC, non-seekable)",
            3 => "disposable inter frame (H.263 only)",
            4 => "generated key frame( server use only)",
            5 => "video info/command frame",
        ];
        if (isset($frameTypeNameTable[$type])) {
            return $frameTypeNameTable[$type];
        }
        return "UnknownFrameType";
    }
    static function getVideoCodecIDName($id) {
        static $codecIDNameTable = [
            2 => "Sorenson H.263",
            3 => "Screen video",
            4 => "On2 VP6",
            5 => "On2 VP6 with alpha channel",
            6 => "Screen video version 2",
            7 => "AVC",
        ];
        if (isset($codecIDNameTable[$id])) {
            return $codecIDNameTable[$id];
        }
        return "UnknownCodecID";
    }

    function parse($bitin, $dataSize) {
        list($startOffset, $dummy) = $bitin->getOffset();
        $this->FrameType = $bitin->getUIBits(4);
        $this->CodecID = $bitin->getUIBits(4);
        if ($this->CodecID === 7) {
            $this->AVCPacketType = $bitin->getUI8();
            $this->CompositionTime = $bitin->getUIBits(24);
        }
        list($bodyStartOffset, $dummy) = $bitin->getOffset();
        $this->Data = $bitin->getData($dataSize - ($bodyStartOffset - $startOffset));
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
