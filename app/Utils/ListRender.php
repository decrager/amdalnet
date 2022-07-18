<?php
namespace App\Utils;

class ListRender
{
    public static function parsingList($text, $font_style = '', $font_size = '') 
    {
        $replaced_text = self::replaceHtmlDefaultList('ol', $text, $font_style, $font_size);
        $replaced_text = self::replaceHtmlDefaultList('ul', $replaced_text, $font_style, $font_size);
        return $replaced_text;
    }

    private static function replaceHtmlDefaultList($type, $text, $font_style, $font_size)
    {
        $new_string = '';
        $tag_open = $type == 'ol' ? '<ol>' : '<ul>';
        $tag_close = $type == 'ol' ? '</ol>' : '</ul>';
        $arr_number = str_replace($tag_close, $tag_open, $text);
        $arr_number = explode($tag_open, $arr_number);
        for($i = 0; $i < count($arr_number); $i++) {
            if(strlen($arr_number[$i]) >= 4) {
                $check_li = substr(trim($arr_number[$i]),0,4);
                if($check_li === '<li>') {
                    $arr_string = str_replace('</li>','</span>',$arr_number[$i]);
                    if($type == 'ol') {
                        $arr_string = explode('<li>', $arr_string);
                        $total = 1;
                        for($a = 0; $a < count($arr_string); $a++) {
                            if(trim($arr_string[$a])) {
                                $new_string .= '<span style="display:inline-block; font-family: ' . $font_style .'; font-size: ' . $font_size . '; padding: 0; margin: 0;">' . $total . '. ' . $arr_string[$a];
                                $total++;
                            }
                        } 
                    } else {
                        $new_string .= str_replace('<li>', '<span style="display:inline-block; font-family: ' . $font_style .'; font-size: ' . $font_size . '; padding: 0; margin: 0;">&bull; ', $arr_string);
                    }
                } else {
                    $new_string .= $arr_number[$i];
                }
            } else {
                $new_string .= $arr_number[$i];
            }
        }
        return str_replace('</li>', '</span>', $new_string);
    }
}