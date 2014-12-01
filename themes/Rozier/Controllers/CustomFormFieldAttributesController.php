<?php
/**
 * Copyright REZO ZERO 2014
 *
 *
 *
 *
 * @file CustomFormsController.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\CustomForm;
use RZ\Roadiz\Core\Entities\CustomFormField;
use RZ\Roadiz\Core\Entities\CustomFormFieldAttribute;
use RZ\Roadiz\Core\Entities\CustomFormAnswer;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Themes\Rozier\RozierApp;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
* CustomForm controller
*/
class CustomFormFieldAttributesController extends RozierApp
{
    /**
     * List every node-types.
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int                                      $customFormAnswerId
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request, $customFormAnswerId)
    {
        $this->validateAccessForRole('ROLE_ACCESS_CUSTOMFORMS');
        /*
         * Manage get request to filter list
         */

         $customFormAnswer = $this->getService("em")->find("RZ\Roadiz\Core\Entities\CustomFormAnswer", $customFormAnswerId);

        $listManager = new EntityListManager(
            $request,
            $this->getService('em'),
            'RZ\Roadiz\Core\Entities\CustomFormFieldAttribute',
            array("customFormAnswer" => $customFormAnswer)
        );
        $listManager->handle();

        $customFormAnswer = $this->getService('em')->find('RZ\Roadiz\Core\Entities\CustomFormAnswer', $customFormAnswerId);

        $this->assignation['filters'] = $listManager->getAssignation();
        $this->assignation['fields'] = $listManager->getEntities();
        $this->assignation['customFormId'] = $customFormAnswer->getCustomForm()->getId();

        return new Response(
            $this->getTwig()->render('custom-form-field-attributes/list.html.twig', $this->assignation),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );
    }
}
