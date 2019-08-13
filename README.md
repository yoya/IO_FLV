# IO_FLV
flv (flash video format) parser &amp; builder
# Usage

```
% composer require yoya/io_flv
% php vendor/yoya/io_flv/sample/flvdump.php
Usage: php flvdump.php [-h] -f <flvfile>
% php vendor/yoya/io_flv/sample/flvdump.php -f input.flv
Signature:FLV Version:1 Audio:1 Video:1 DataOffset:9
[0] PreviousTagSize:0 Filter:0 TagType:18(ScriptTag)
  DataSize:358 Timestamp:0 StreamID:0
  Type:2(String) Value:onMetaData
  Type:8(ECMA array) Length:16
  Name:duration: Value:
    Type:0(Number) Value:2.653
  Name:width: Value:
    Type:0(Number) Value:1920
(...omit...)
[1] PreviousTagSize:369 Filter:0 TagType:8(AudioTag)
  DataSize:193 Timestamp:0 StreamID:0
SoundFormat:2(MP3) SoundRate:3(44 kHz)
SoundSize:1(16-bit samples) SoundType:0(Mono sound)
             0  1  2  3  4  5  6  7   8  9  a  b  c  d  e  f  0123456789abcdef
0x00000000  ff fb 54 c4 00 00 07 5c  03 5b b4 10 80 21 9e 14    T    \ [   !
[2] PreviousTagSize:204 Filter:0 TagType:9(VideoTag)
  DataSize:147336 Timestamp:23 StreamID:0
FrameType:1(key frame (AVC, seekable)) CodecID:2(Sorenson H.263)
             0  1  2  3  4  5  6  7   8  9  a  b  c  d  e  f  0123456789abcdef
0x00000000  00 00 84 00 83 c0 02 1c  13 be 17 e2 b5 5c 1f fb               \
(...omit...)
```

# reference

- https://www.adobe.com/devnet/f4v.html
- https://github.com/yoya/IO_FLV/wiki/FLV
