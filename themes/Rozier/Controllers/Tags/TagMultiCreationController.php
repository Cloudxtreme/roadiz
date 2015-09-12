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
 * @file TagMultiCreationController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Tags;

use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Events\FilterTagEvent;
use RZ\Roadiz\Core\Events\TagEvents;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\Forms\TagTranslationType;
use Themes\Rozier\Forms\TagType;
use Themes\Rozier\RozierApp;
use Themes\Rozier\Widgets\TagTreeWidget;

/**
 * {@inheritdoc}
 */
class TagMultiCreationController extends RozierApp
{
    public function addChildAction(Request $request, $parentTagId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_TAGS');

        $translation = $this->getService('defaultTranslation');
        $parentTag = $this->getService('em')
                          ->find('RZ\Roadiz\Core\Entities\Tag', (int) $parentTagId);

        if (null !== $parentTag) {
            $form = $this->buildAddForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $names = explode(',', $data['names']);

                foreach ($names as $name) {
                    $name = strip_tags(trim($name));

                    $tag = new Tag();
                    $tag->setTagName($name);
                    $tag->setParent($parentTag);
                    $this->getService('em')->persist($tag);

                    $translatedTag = new TagTranslation($tag, $translation);
                    $this->getService('em')->persist($translatedTag);
                    $this->getService('em')->flush();

                    /*
                     * Dispatch event
                     */
                    $event = new FilterTagEvent($tag);
                    $this->getService('dispatcher')->dispatch(TagEvents::TAG_CREATED, $event);

                    $msg = $this->getTranslator()->trans('child.tag.%name%.created', ['%name%' => $tag->getTagName()]);
                    $this->publishConfirmMessage($request, $msg);
                }

                return $this->redirect($this->generateUrl('tagsTreePage', ['tagId' => $parentTagId]));
            }

            $this->assignation['translation'] = $translation;
            $this->assignation['form'] = $form->createView();
            $this->assignation['tag'] = $parentTag;

            return $this->render('tags/add-multiple.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * @param RZ\Roadiz\Core\Entities\Tag $tag
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm()
    {
        $builder = $this->createFormBuilder()
                        ->add('names', 'textarea', [
                            'label' => 'tags.names',
                            'attr' => [
                                'placeholder' => 'write.every.tags.names.comma.separated',
                            ],
                            'constraints' => [
                                new NotBlank(),
                            ],
                        ]);

        return $builder->getForm();
    }
}
