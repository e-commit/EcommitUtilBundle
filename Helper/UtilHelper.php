<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Helper;

class UtilHelper
{
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

    public function table($values, $size, $tableOptions = array(), $trOptions = array(), $tdOptions = array())
    {
        $countValues = \count($values);
        if (!is_array($values) || $countValues == 0) {
            return '';
        }
        if ($countValues % $size > 0) {
            for ($i = 0; $i < ($size - $countValues % $size); $i++) {
                $values[] = '';
            }
        }

        $tableContent = '';
        foreach (\array_chunk($values, $size) as $tr) {
            $trContent = '';
            foreach ($tr as $td) {
                $trContent .= $this->tag('td', $tdOptions, $td);
            }
            $tableContent .= $this->tag('tr', $trOptions, $trContent);
        }

        return $this->tag('table', $tableOptions, $tableContent);
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
        if (!$name) {
            return '';
        }
        $htmlOptions = '';
        foreach ($options as $key => $value) {
            $htmlOptions .= ' ' . $key . '="' . \htmlspecialchars($value, \ENT_COMPAT) . '"';
        }
        if ($content !== null) {
            return '<' . $name . $htmlOptions . '>' . $content . '</' . $name . '>';
        } else {
            return '<' . $name . $htmlOptions . (($open) ? '>' : ' />');
        }
    }
}