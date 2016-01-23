<?php

if ( ! function_exists('normalise_path')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function normalise_path($path)
    {
        $path = preg_replace("://+:", "/", $path);

        $npath = $path;
        $path  = "";
        while ($npath != $path) {
            $path = $npath;

            $npath = preg_replace(":(^|/)\./:", "$1", $npath);
        }

        $path = "";
        while ($npath != $path) {
            $path = $npath;

            $npath = preg_replace(":(^|/)[^/]+/\.\./:", "$1", $npath);
        }

        return $npath;
    }
}

if ( ! function_exists('file_which')) {
    function file_which($path)
    {
        $paths = explode(PATH_SEPARATOR, get_include_path());

        if (count($paths) == 0) {
            if (file_exists($path)) {
                return $path;
            } else {
                return false;
            }
        }

        foreach ($paths as $p) {
            $x = normalise_path($p . "/" . $path);

            if (file_exists($x)) {
                return $x;
            }
        }

        return false;
    }
}

if ( ! function_exists('parseHTMLColour')) {
    function parseHTMLColour($colour)
    {
        $colour = trim($colour);

        if (preg_match("/^#([0123456789ABCDEF]{2})([0123456789ABCDEF]{2})([0123456789ABCDEF]{2})$/i", $colour, $regs)) {
            $red   = $regs[1];
            $green = $regs[2];
            $blue  = $regs[3];
        } elseif (preg_match("/^#([0123456789ABCDEF]{3})$/i", $colour, $regs)) {
            $red   = $regs[1][0] . $regs[1][0];
            $green = $regs[1][1] . $regs[1][1];
            $blue  = $regs[1][2] . $regs[1][2];
        }

        if (isset($red)) {
            $out = array();

            $out["red"]   = hexdec($red);
            $out["green"] = hexdec($green);
            $out["blue"]  = hexdec($blue);

            return $out;
        }

        /* TODO: Add support for standard named HTML colours
                switch($colour) {
                }
         */

        return array(
            "red"   => 255,
            "green" => 255,
            "blue"  => 255
        );
    }
}