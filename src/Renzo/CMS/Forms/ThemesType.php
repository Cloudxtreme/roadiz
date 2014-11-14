<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file ThemesType.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Renzo\CMS\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Theme;
use RZ\Renzo\Core\Exceptions\ThemeClassNotValidException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Finder\Finder;

/**
 * Theme selector form field type.
 */
class ThemesType extends AbstractType
{
    protected $themes;
    private $choices;

    public function __construct() {
        $themes = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\Theme')
            ->findAll();

        $choices = array();

        $finder = new Finder();

        // Extracting the PHP files from every Theme folder
        $iterator = $finder
            ->files()
            ->name('config.json')
            ->depth(1)
            ->in(RENZO_ROOT.'/themes');

        // And storing it into an array, used in the form
        foreach ($iterator as $file) {
            $data = json_decode(file_get_contents($file->getPathname()), true);
            //var_dump($data);
            //var_dump($file->getRelativePathname());
            //exit;
            // ob_start();
            $classPath = RENZO_ROOT.'/themes/'.$file->getRelativePathname();
            // include_once $classPath;
            // $namespace = str_replace('/', '\\', $file->getRelativePathname());
            $classname = '\Themes\\'.$data['themeDir']."\\".$data['themeDir']."App";//str_replace('.php', '', $namespace);
            // ob_end_clean();

            /*
             * Parsed file is not or does not contain any PHP Class
             * Bad Theme !
             */
            //if (class_exists($classname)) {
            $choices[$classname] = $data['themeDir']."App".": ".$data['name'];
            //} else {
            //    throw new ThemeClassNotValidException($classPath . " file does not contain any valid PHP Class.", 1);
            //}
        }
        foreach ($themes as $theme) {
            if (array_key_exists($theme->getClassName(), $choices)) {
                unset($choices[$theme->getClassName()]);
            }
            if (array_key_exists(Kernel::INSTALL_CLASSNAME, $choices)) {
                unset($choices[Kernel::INSTALL_CLASSNAME]);
            }
        }
        $this->choices = $choices;
    }

    public function getSize() {
        return (count($this->choices));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->choices
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'classname';
    }
}
