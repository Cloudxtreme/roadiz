<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ParsedownExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

class ParsedownExtension extends \Twig_Extension
{
    /**
     * @var \Parsedown
     */
    protected $parsedown;
    /**
     * @var \ParsedownExtra
     */
    protected $parsedownExtra;

    public function __construct()
    {
        $this->parsedown = new \Parsedown();
        $this->parsedownExtra = new \ParsedownExtra();
    }

    public function getName()
    {
        return 'parsedownExtension';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('markdown', [$this, 'markdown'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('inlineMarkdown', [$this, 'inlineMarkdown'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('markdownExtra', [$this, 'markdownExtra'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $text
     * @return string
     */
    public function markdown($text)
    {
        return $this->parsedown->text($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function inlineMarkdown($text)
    {
        return $this->parsedown->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function markdownExtra($text)
    {
        return $this->parsedownExtra->text($text);
    }
}
