<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodeType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 *
 */
class NodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nodeName', 'text', [
                'label' => 'nodeName',
                'constraints' => [
                    new NotBlank(),
                    new UniqueNodeName([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['nodeName'],
                    ]),
                ],
            ])
            ->add('home', 'checkbox', [
                'label' => 'node.isHome',
                'required' => false,
                'attr' => ['class' => 'rz-boolean-checkbox'],
            ])
            ->add('dynamicNodeName', 'checkbox', [
                'label' => 'node.dynamicNodeName',
                'required' => false,
                'attr' => [
                    'class' => 'rz-boolean-checkbox',
                    'data-desc' => 'dynamic_node_name_will_follow_any_title_change_on_default_translation'
                ],
            ])
            ->add('childrenOrder', 'choice', [
                'label' => 'node.childrenOrder',
                'choices_as_values' => true,
                'choices' => [
                    'position' => 'position',
                    'nodeName' => 'nodeName',
                    'createdAt' => 'createdAt',
                    'updatedAt' => 'updatedAt',
                    'publishedAt' => 'ns.publishedAt',
                ],
            ])
            ->add('childrenOrderDirection', 'choice', [
                'label' => 'node.childrenOrderDirection',
                'choices_as_values' => true,
                'choices' => [
                    'ascendant' => 'ASC',
                    'descendant' => 'DESC',
                ],
            ]);
    }

    public function getName()
    {
        return 'node';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'label' => false,
            'nodeName' => null,
            'data_class' => 'RZ\Roadiz\Core\Entities\Node',
            'attr' => [
                'class' => 'uk-form node-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes('em', 'Doctrine\Common\Persistence\ObjectManager');
        $resolver->setAllowedTypes('nodeName', 'string');
    }
}
