<?php
namespace Trois\ElasticSearch\Utility;

class Needle {

  public static function clean($string)
  {
    $string = trim(urldecode($string));
    $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";
    $string = preg_replace_callback ($regex,
    function ($matches) {
      return "\\" . $matches[0];
    }, $string);
    return $string;
  }

}
