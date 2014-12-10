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
 * @file BackendController.php
 * @author Ambroise Maupate
 */

namespace Themes\Rozier;

use RZ\Roadiz\CMS\Controllers\BackendController;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Bags\SettingsBag;

use Themes\Rozier\Widgets\NodeTreeWidget;
use Themes\Rozier\Widgets\TagTreeWidget;
use Themes\Rozier\Widgets\FolderTreeWidget;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Pimple\Container;

/**
 * Rozier main theme application
 */
class RozierApp extends BackendController
{
    protected static $themeName =      'Rozier administration theme';
    protected static $themeAuthor =    'Ambroise Maupate, Julien Blanchet';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir =       'Rozier';

    protected $formFactory = null;

    /**
     * @return array $assignation
     */
    public function prepareBaseAssignation()
    {
        parent::prepareBaseAssignation();

        if (!$this->getKernel()->getRequest()->isXmlHttpRequest()) {
            $this->assignation['nodeTree'] = new NodeTreeWidget($this->getKernel()->getRequest(), $this);
            $this->assignation['tagTree'] = new TagTreeWidget($this->getKernel()->getRequest(), $this);
            $this->assignation['folderTree'] = new FolderTreeWidget($this->getKernel()->getRequest(), $this);
            $this->assignation['backofficeEntries'] = $this->getService('backoffice.entries');
        }

        //Settings
        $this->assignation['head']['siteTitle'] = SettingsBag::get('site_name').' back-office';
        $this->assignation['head']['mapsStyle'] = SettingsBag::get('maps_style');

        $this->assignation['head']['mainColor'] = SettingsBag::get('main_color');
        $this->assignation['head']['googleClientId'] = SettingsBag::get('google_client_id') ? SettingsBag::get('google_client_id') : "";

        $this->assignation['head']['grunt'] = include(dirname(__FILE__).'/static/public/config/assets.config.php');

        $this->assignation['settingGroups'] = $this->getService('em')
                                                   ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                                                   ->findBy(array('inMenu' => true), array('name'=>'ASC'));

        /*
         * Get admin image
         */
        $adminImage = $this->getService('em')
                           ->getRepository('RZ\Roadiz\Core\Entities\DocumentTranslation')
                           ->findOneBy(array(
                                'name' => '_admin_image_'
                            ));
        if (null !== $adminImage) {
            $this->assignation['adminImage'] = $adminImage->getDocument();
        }

        $this->assignation['nodeStatuses'] = array(
            'draft' => Node::DRAFT,
            'pending' => Node::PENDING,
            'published' => Node::PUBLISHED,
            'archived' => Node::ARCHIVED,
            'deleted' => Node::DELETED
        );

        return $this;
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response $response
     */
    public function indexAction(Request $request)
    {
        return new Response(
            $this->getTwig()->render('index.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }

    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response $response
     */
    public function cssAction(Request $request)
    {

        $this->assignation['mainColor'] = SettingsBag::get('main_color');

        return new Response(
            $this->getTwig()->render('css/mainColor.css.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/css')
        );
    }

    /**
     * Append objects to global container.
     *
     * @param Pimple\Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        BackendController::setupDependencyInjection($container);

        $container->extend('backoffice.entries', function (array $entries, $c) {

            $entries['dashboard'] = array(
                'name' => 'dashboard',
                'path' => $c['urlGenerator']->generate('adminHomePage'),
                'icon' => 'uk-icon-rz-dashboard',
                'roles' => null,
                'subentries' => null
            );
            $entries['nodes'] = array(
                'name' => 'nodes',
                'path' => null,
                'icon' => 'uk-icon-rz-global-nodes',
                'roles' => array('ROLE_ACCESS_NODES'),
                'subentries' => array(
                    'all.nodes' => array(
                        'name' => 'all.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomePage'),
                        'icon' => 'uk-icon-rz-all-nodes',
                        'roles' => null
                    ),
                    'draft.nodes' => array(
                        'name' => 'draft.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomeDraftPage'),
                        'icon' => 'uk-icon-rz-draft-nodes',
                        'roles' => null
                    ),
                    'pending.nodes' => array(
                        'name' => 'pending.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomePendingPage'),
                        'icon' => 'uk-icon-rz-pending-nodes',
                        'roles' => null
                    ),
                    'archived.nodes' => array(
                        'name' => 'archived.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomeArchivedPage'),
                        'icon' => 'uk-icon-rz-archives-nodes',
                        'roles' => null
                    ),
                    'deleted.nodes' => array(
                        'name' => 'deleted.nodes',
                        'path' => $c['urlGenerator']->generate('nodesHomeDeletedPage'),
                        'icon' => 'uk-icon-rz-deleted-nodes',
                        'roles' => null
                    ),
                    'search.nodes' => array(
                        'name' => 'search.nodes',
                        'path' => $c['urlGenerator']->generate('searchNodePage'),
                        'icon' => 'uk-icon-search',
                        'roles' => null
                    ),
                )
            );
            $entries['manage.documents'] = array(
                'name' => 'manage.documents',
                'path' => $c['urlGenerator']->generate('documentsHomePage'),
                'icon' => 'uk-icon-rz-documents',
                'roles' => array('ROLE_ACCESS_DOCUMENTS'),
                'subentries' => null
            );
            $entries['manage.tags'] = array(
                'name' => 'manage.tags',
                'path' => $c['urlGenerator']->generate('tagsHomePage'),
                'icon' => 'uk-icon-rz-tags',
                'roles' => array('ROLE_ACCESS_TAGS'),
                'subentries' => null
            );
            $entries['construction'] = array(
                'name' => 'construction',
                'path' => null,
                'icon' => 'uk-icon-rz-construction',
                'roles' => array('ROLE_ACCESS_NODETYPES', 'ROLE_ACCESS_TRANSLATIONS', 'ROLE_ACCESS_THEMES', 'ROLE_ACCESS_FONTS'),
                'subentries' => array(
                    'manage.nodeTypes' => array(
                        'name' => 'manage.nodeTypes',
                        'path' => $c['urlGenerator']->generate('nodeTypesHomePage'),
                        'icon' => 'uk-icon-rz-manage-nodes',
                        'roles' => array('ROLE_ACCESS_NODETYPES')
                    ),
                    'manage.translations' => array(
                        'name' => 'manage.translations',
                        'path' => $c['urlGenerator']->generate('translationsHomePage'),
                        'icon' => 'uk-icon-rz-translate',
                        'roles' => array('ROLE_ACCESS_TRANSLATIONS')
                    ),
                    'manage.themes' => array(
                        'name' => 'manage.themes',
                        'path' => $c['urlGenerator']->generate('themesHomePage'),
                        'icon' => 'uk-icon-rz-themes',
                        'roles' => array('ROLE_ACCESS_THEMES')
                    ),
                    'manage.fonts' => array(
                        'name' => 'manage.fonts',
                        'path' => $c['urlGenerator']->generate('fontsHomePage'),
                        'icon' => 'uk-icon-rz-fontes',
                        'roles' => array('ROLE_ACCESS_FONTS')
                    ),
                )
            );

            $entries['user.system'] = array(
                'name' => 'user.system',
                'path' => null,
                'icon' => 'uk-icon-rz-users',
                'roles' => array('ROLE_ACCESS_USERS', 'ROLE_ACCESS_ROLES', 'ROLE_ACCESS_GROUPS'),
                'subentries' => array(
                    'manage.users' => array(
                        'name' => 'manage.users',
                        'path' => $c['urlGenerator']->generate('usersHomePage'),
                        'icon' => 'uk-icon-rz-user',
                        'roles' => array('ROLE_ACCESS_USERS')
                    ),
                    'manage.roles' => array(
                        'name' => 'manage.roles',
                        'path' => $c['urlGenerator']->generate('rolesHomePage'),
                        'icon' => 'uk-icon-rz-roles',
                        'roles' => array('ROLE_ACCESS_ROLES')
                    ),
                    'manage.groups' => array(
                        'name' => 'manage.groups',
                        'path' => $c['urlGenerator']->generate('groupsHomePage'),
                        'icon' => 'uk-icon-rz-groups',
                        'roles' => array('ROLE_ACCESS_GROUPS')
                    )
                )
            );

            $entries['interactions'] = array(
                'name' => 'interactions',
                'path' => null,
                'icon' => 'uk-icon-rz-interactions',
                'roles' => array(
                    'ROLE_ACCESS_CUSTOMFORMS',
                    'ROLE_ACCESS_NEWSLETTERS',
                    'ROLE_ACCESS_MANAGE_SUBSCRIBERS',
                    'ROLE_ACCESS_COMMENTS'
                ),
                'subentries' => array(
                    'manage.customForms' => array(
                        'name' => 'manage.customForms',
                        'path' => $c['urlGenerator']->generate('customFormsHomePage'),
                        'icon' => 'uk-icon-rz-surveys',
                        'roles' => array('ROLE_ACCESS_CUSTOMFORMS')
                    ),
                    'manage.newsletters' => array(
                        'name' => 'manage.newsletters',
                        'path' => null,
                        'icon' => 'uk-icon-rz-newsletters',
                        'roles' => array('ROLE_ACCESS_NEWSLETTERS')
                    ),
                    'manage.subscribers' => array(
                        'name' => 'manage.subscribers',
                        'path' => null,
                        'icon' => 'uk-icon-rz-subscribers',
                        'roles' => array('ROLE_ACCESS_MANAGE_SUBSCRIBERS')
                    ),
                    'manage.comments' => array(
                        'name' => 'manage.comments',
                        'path' => null,
                        'icon' => 'uk-icon-rz-comments',
                        'roles' => array('ROLE_ACCESS_COMMENTS')
                    ),
                )
            );

            $entries['settings'] = array(
                'name' => 'settings',
                'path' => null,
                'icon' => 'uk-icon-rz-settings',
                'roles' => array('ROLE_ACCESS_SETTINGS'),
                'subentries' => array(
                    'all.settings' => array(
                        'name' => 'all.settings',
                        'path' => $c['urlGenerator']->generate('settingsHomePage'),
                        'icon' => 'uk-icon-rz-settings-general',
                        'roles' => null
                    ),
                    /*
                     * This entry is dynamic
                     */
                    'setting.groups.dynamic' => array(
                        'name' => 'setting.groups.dynamic',
                        'path' => 'settingGroupsSettingsPage',
                        'icon' => 'uk-icon-rz-settings-group',
                        'roles' => null
                    ),
                    'setting.groups' => array(
                        'name' => 'setting.groups',
                        'path' => $c['urlGenerator']->generate('settingGroupsHomePage'),
                        'icon' => 'uk-icon-rz-settings-groups',
                        'roles' => null
                    )
                )
            );

            return $entries;
        });
    }
}
