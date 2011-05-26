<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) Hubert LECORCHE <hlecorche@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Helper;

use Symfony\Component\Translation\TranslatorInterface;

class UtilHelper
{
    protected $translator = null;
    
    /**
     * Constructor
     * 
     * @param TranslatorInterface $translator 
     */
    public function __construct(TranslatorInterface $translator = null) 
    {
        $this->translator = $translator;
    }
    
    /**
     * Constructs an html tag
     * 
     * @param string $name Tag name
     * @param array $options Tag options
     * @param string $content Content
     * @param bool $open True to leave tag open
     * @return string 
     */
    public function tag($name, Array $options = array(), $content = null, $open = false)
    {
        if (!$name)
        {
            return '';
        }
        $options_html = '';
        foreach ($options as $key => $value)
        {
            $options_html .= ' '.$key.'="'.\htmlspecialchars($value, \ENT_COMPAT).'"';
        }
        if($content)
        {
            return '<'.$name.$options_html.'>'.$content.'</'.$name.'>';
        }
        else
        {
            return '<'.$name.$options_html.(($open) ? '>' : ' />');
        }
    }
    
    /**
     * Escapes text for Javascript
     * 
     * @param string $javascript Input Text
     * @return string 
     */
    public function escape_javascript($javascript = '')
    {
      $javascript = preg_replace('/\r\n|\n|\r/', "\\n", $javascript);
      $javascript = preg_replace('/(["\'])/', '\\\\\1', $javascript);

      return $javascript;
    }
    
    /**
     * Translates text
     * 
     * @param string $text Input text
     * @return string 
     */
    public function translate($text)
    {
        if(is_null($this->translator))
        {
            return $text;
        }
        return $this->translator->trans($text);
    }
}