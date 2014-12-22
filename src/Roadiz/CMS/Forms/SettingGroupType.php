<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file SettingGroupType.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Group setting selector form field type.
 */
class SettingGroupType extends AbstractType
{
    protected $themes;

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $groups = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
            ->findAll();

        $choices = array();

        foreach ($groups as $group) {
            $choices[$group->getId()] = $group->getName();
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
            'empty_value' => '---------'
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
        return 'settingGroups';
    }
}
