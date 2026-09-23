<?php
// Simple Barcode Generator - No external dependencies required
// Place this file at: includes/simple_barcode.php

class SimpleBarcode {
    /**
     * Generate a simple barcode image
     * @param string $text The text to encode in barcode
     * @param int $width Image width
     * @param int $height Image height
     * @return string PNG image data
     */
    public static function generate($text, $width = 300, $height = 80) {
        // Create image
        $img = imagecreate($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        
        // Fill background
        imagefilledrectangle($img, 0, 0, $width, $height, $white);
        
        // Generate barcode pattern from text
        $pattern = self::getPattern($text);
        $x = 20;
        $barWidth = 2;
        
        for($i = 0; $i < strlen($pattern); $i++) {
            if($pattern[$i] == '1') {
                imagefilledrectangle($img, $x, 20, $x + $barWidth, $height - 30, $black);
            }
            $x += $barWidth;
        }
        
        // Add text below barcode
        $fontSize = 5;
        $textWidth = strlen($text) * imagefontwidth($fontSize);
        $textX = ($width - $textWidth) / 2;
        imagestring($img, $fontSize, $textX, $height - 20, $text, $black);
        
        // Output as PNG
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);
        
        return $data;
    }
    
    /**
     * Generate a pattern from text
     */
    private static function getPattern($text) {
        $pattern = '';
        for($i = 0; $i < strlen($text); $i++) {
            $code = ord($text[$i]);
            $binary = decbin($code);
            $pattern .= $binary;
        }
        // Limit length and repeat for better barcode
        $pattern = str_repeat(substr($pattern, 0, 100), 2);
        return substr($pattern, 0, 200);
    }
    
    /**
     * Generate HTML/CSS based barcode (for browsers)
     */
    public static function generateHTML($text) {
        $chars = str_split($text);
        $html = '<div class="css-barcode" style="font-family: monospace; text-align: center;">';
        
        foreach($chars as $char) {
            $code = ord($char);
            $binary = str_pad(decbin($code), 8, '0', STR_PAD_LEFT);
            $html .= '<div style="display: inline-block;">';
            for($i = 0; $i < strlen($binary); $i++) {
                $width = $binary[$i] == '1' ? '3px' : '1px';
                $html .= '<div style="display: inline-block; width: ' . $width . '; height: 50px; background: black;"></div>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div style="text-align: center; font-family: monospace; font-size: 14px; margin-top: 10px;">' . $text . '</div>';
        
        return $html;
    }
}
?>