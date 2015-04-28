<?php
/*
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
 *
 * @file UsersController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers\Users;

use RZ\Roadiz\CMS\Forms\Constraints\ValidFacebookName;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;
use RZ\Roadiz\Core\Exceptions\FacebookUsernameNotFoundException;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use RZ\Roadiz\Utils\MediaFinders\FacebookPictureFinder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Themes\Rozier\RozierApp;

/**
 * {@inheritdoc}
 */
class UsersController extends RozierApp
{
    /**
     * List every users.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        /*
         * Manage get request to filter list
         */
        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\User'
        );
        $listManager->handle();

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['users'] = $listManager->getEntities();

        return $this->render('users/list.html.twig', $this->assignation);
    }

    /**
     * Return an edition form for requested user.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $userId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $userId)
    {
        $this->validateAccessForRole('ROLE_BACKEND_USER');

        if (!($this->isGranted('ROLE_ACCESS_USERS')
            || $this->getUser()->getId() == $userId)) {
            throw $this->createAccessDeniedException("You don't have access to this page: ROLE_ACCESS_USERS");
        }

        $user = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;
            $form = $this->buildEditForm($user);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->editUser($form->getData(), $user, $request);
                    $msg = $this->getTranslator()->trans(
                        'user.%name%.updated',
                        ['%name%' => $user->getUsername()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (FacebookUsernameNotFoundException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl(
                    'usersEditPage',
                    ['userId' => $user->getId()]
                ));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/edit.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return an creation form for requested user.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS');

        $user = new User();

        if ($user !== null) {
            $this->assignation['user'] = $user;
            $form = $this->buildAddForm($user);

            $form->handleRequest();

            if ($form->isValid()) {
                try {
                    $this->addUser($form->getData(), $user);
                    $user->getViewer()->sendSignInConfirmation();

                    $msg = $this->getTranslator()->trans('user.%name%.created', ['%name%' => $user->getUsername()]);
                    $this->publishConfirmMessage($request, $msg);

                    return $this->redirect($this->generateUrl('usersHomePage'));
                } catch (FacebookUsernameNotFoundException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/add.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }

    /**
     * Return a deletion form for requested user.
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $userId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $userId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_USERS_DELETE');

        $user = $this->getService('em')
                     ->find('RZ\Roadiz\Core\Entities\User', (int) $userId);

        if ($user !== null) {
            $this->assignation['user'] = $user;

            $form = $this->buildDeleteForm($user);

            $form->handleRequest();

            if ($form->isValid() &&
                $form->getData()['userId'] == $user->getId()) {
                try {
                    $this->deleteUser($form->getData(), $user);

                    $msg = $this->getTranslator()->trans(
                        'user.%name%.deleted',
                        ['%name%' => $user->getUsername()]
                    );
                    $this->publishConfirmMessage($request, $msg);
                } catch (EntityAlreadyExistsException $e) {
                    $this->publishErrorMessage($request, $e->getMessage());
                }
                /*
                 * Force redirect to avoid resending form when refreshing page
                 */
                return $this->redirect($this->generateUrl('usersHomePage'));
            }

            $this->assignation['form'] = $form->createView();

            return $this->render('users/delete.html.twig', $this->assignation);
        } else {
            return $this->throw404();
        }
    }
    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    private function editUser($data, User $user, Request $request)
    {
        if ($data['username'] != $user->getUsername() &&
            $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\User')
            ->usernameExists($data['username'])
        ) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'user.%name%.cannot_update.name_already_exists',
                    ['%name%' => $data['username']]
                ),
                1
            );
        }
        if ($data['email'] != $user->getEmail() &&
            $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\User')
            ->emailExists($data['email'])) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'user.%name%.cannot_update.email_already_exists',
                    ['%email%' => $data['email']]
                ),
                1
            );
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucwords($key);
            if ($key == "chroot") {
                if (count($value) > 1) {
                    $msg = $this->getTranslator()->trans('chroot.limited.one');
                    $this->publishErrorMessage($request, $msg);
                }
                if ($value !== null) {
                    $n = $this->getService('em')->find("RZ\Roadiz\Core\Entities\Node", $value[0]);
                    $user->$setter($n);
                } else {
                    $user->$setter(null);
                }
            } else {
                $user->$setter($value);
            }
        }

        $this->updateProfileImage($user);
        $this->getService('em')->flush();
    }

    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    private function addUser($data, User $user)
    {
        if ($this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\User')
            ->usernameExists($data['username']) ||
            $this->getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\User')
            ->emailExists($data['email'])) {
            throw new EntityAlreadyExistsException(
                $this->getTranslator()->trans(
                    'user.%name%.cannot_create_already_exists',
                    ['%name%' => $data['username']]
                ),
                1
            );
        }

        foreach ($data as $key => $value) {
            $setter = 'set' . ucwords($key);
            $user->$setter($value);
        }

        $this->updateProfileImage($user);
        $this->getService('em')->persist($user);
        $this->getService('em')->flush();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    private function updateProfileImage(User $user)
    {
        if ($user->getFacebookName() != '') {
            try {
                $facebook = new FacebookPictureFinder($user->getFacebookName());
                $url = $facebook->getPictureUrl();
                $user->setPictureUrl($url);
            } catch (\Exception $e) {
                $url = "http://www.gravatar.com/avatar/" .
                md5(strtolower(trim($user->getEmail()))) .
                "?d=identicon&s=200";
                $user->setPictureUrl($url);
                throw new FacebookUsernameNotFoundException(
                    $this->getTranslator()->trans(
                        'user.facebook_name.%name%._does_not_exist',
                        ['%name%' => $user->getFacebookName()]
                    ),
                    1
                );
            }
        } else {
            $url = "http://www.gravatar.com/avatar/" .
            md5(strtolower(trim($user->getEmail()))) .
            "?d=identicon&s=200";
            $user->setPictureUrl($url);
        }
    }

    /**
     * @param array                       $data
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    private function deleteUser($data, User $user)
    {
        $this->getService('em')->remove($user);
        $this->getService('em')->flush();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildAddForm(User $user)
    {
        $builder = $this->createFormBuilder();

        $this->buildCommonFormFields($builder, $user);

        return $builder->getForm();
    }

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildEditForm(User $user)
    {
        $defaults = [
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'company' => $user->getCompany(),
            'job' => $user->getJob(),
            'birthday' => $user->getBirthday(),
            'facebookName' => $user->getFacebookName(),
        ];

        $builder = $this->getService('formFactory')
                        ->createNamedBuilder('source', 'form', $defaults);
        $this->buildCommonFormFields($builder, $user);

        return $builder->getForm();
    }

    /**
     * Build common fields between add and edit user forms.
     *
     * @param FormBuilder $builder
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    private function buildCommonFormFields(&$builder, User $user)
    {
        $builder->add('email', 'email', [
                    'label' => 'email',
                    'constraints' => [
                        new NotBlank(),
                    ],
                ])
                ->add('username', 'text', [
                    'label' => 'username',
                    'constraints' => [
                        new NotBlank(),
                    ],
                ])
                ->add('plainPassword', 'repeated', [
                    'type' => 'password',
                    'invalid_message' => 'password.must.match',
                    'first_options' => [
                        'label' => 'password',
                    ],
                    'second_options' => [
                        'label' => 'passwordVerify',
                    ],
                    'required' => false,
                ])
                ->add('firstName', 'text', [
                    'label' => 'firstName',
                    'required' => false,
                ])
                ->add('lastName', 'text', [
                    'label' => 'lastName',
                    'required' => false,
                ])
                ->add('facebookName', 'text', [
                    'label' => 'facebookName',
                    'required' => false,
                    'constraints' => [
                        new ValidFacebookName(),
                    ],
                ])
                ->add('company', 'text', [
                    'label' => 'company',
                    'required' => false,
                ])
                ->add('job', 'text', [
                    'label' => 'job',
                    'required' => false,
                ])
                ->add('birthday', 'date', [
                    'label' => 'birthday',
                    'required' => false,
                    'years' => range(1920, date('Y') - 6),
                ]);

        return $builder;
    }

    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     *
     * @return \Symfony\Component\Form\Form
     */
    private function buildDeleteForm(User $user)
    {
        $builder = $this->createFormBuilder()
                        ->add(
                            'userId',
                            'hidden',
                            [
                                'data' => $user->getId(),
                                'constraints' => [
                                    new NotBlank(),
                                ],
                            ]
                        );

        return $builder->getForm();
    }
}
