<?php

namespace PhpXmlRpc\Helper;

class Http
{
    /**
     * decode a string that is encoded w/ "chunked" transfer encoding
     * as defined in rfc2068 par. 19.4.6
     * code shamelessly stolen from nusoap library by Dietrich Ayala
     *
     * @param string $buffer the string to be decoded
     * @return string
     */
    public static function decode_chunked($buffer)
    {
        // length := 0
        $length = 0;
        $new = '';

        // read chunk-size, chunk-extension (if any) and crlf
        // get the position of the linebreak
        $chunkend = strpos($buffer,"\r\n") + 2;
        $temp = substr($buffer,0,$chunkend);
        $chunk_size = hexdec( trim($temp) );
        $chunkstart = $chunkend;
        while($chunk_size > 0)
        {
            $chunkend = strpos($buffer, "\r\n", $chunkstart + $chunk_size);

            // just in case we got a broken connection
            if($chunkend == false)
            {
                $chunk = substr($buffer,$chunkstart);
                // append chunk-data to entity-body
                $new .= $chunk;
                $length += strlen($chunk);
                break;
            }

            // read chunk-data and crlf
            $chunk = substr($buffer,$chunkstart,$chunkend-$chunkstart);
            // append chunk-data to entity-body
            $new .= $chunk;
            // length := length + chunk-size
            $length += strlen($chunk);
            // read chunk-size and crlf
            $chunkstart = $chunkend + 2;

            $chunkend = strpos($buffer,"\r\n",$chunkstart)+2;
            if($chunkend == false)
            {
                break; //just in case we got a broken connection
            }
            $temp = substr($buffer,$chunkstart,$chunkend-$chunkstart);
            $chunk_size = hexdec( trim($temp) );
            $chunkstart = $chunkend;
        }
        return $new;
    }

}