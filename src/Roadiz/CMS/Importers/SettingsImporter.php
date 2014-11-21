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
 * @file SettingsImporter.php
 * @copyright REZO ZERO 2014
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\CMS\Importers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\Entities\SettingGroup;
use Doctrine\Common\Collections\ArrayCollection;
use RZ\Roadiz\Core\Serializers\SettingJsonSerializer;
use RZ\Roadiz\Core\Serializers\SettingCollectionJsonSerializer;

use RZ\Roadiz\CMS\Importers\ImporterInterface;

use RZ\Roadiz\Core\Exceptions\EntityAlreadyExistsException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use \Symfony\Component\Form\Form;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * {@inheritdoc}
 */
class SettingsImporter implements ImporterInterface
{
    /**
     * Import a Json file (.rzt) containing setting and setting group.
     *
     * @param string $serializedData
     *
     * @return bool
     */
    public static function importJsonFile($serializedData)
    {
        $return = false;
        $settingGroups = SettingCollectionJsonSerializer::deserialize($serializedData);
        $groupsNames = Kernel::getService('em')
                  ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                  ->findAllNames();

        $settingsNames = Kernel::getService('em')
                  ->getRepository('RZ\Roadiz\Core\Entities\Setting')
                  ->findAllNames();

        $newSettings = array();

        $newSettingGroups = new ArrayCollection();


        foreach ($settingGroups as $index => $settingGroup) {

            /*
             * Loop over settings to set their group
             * and move them to a temp collection
             */
            foreach ($settingGroup->getSettings() as $setting) {

                if (!in_array($setting->getName(), $settingsNames)) {

                } else {
                    $setting = Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\Setting')
                        ->findOneByName($setting->getName());
                }
                /*
                 * Set array with setting and the deserialize setting's group
                 * to don't take the existing setting's group
                 */
                $newSettings[] = array($setting, $settingGroup);
                $settingGroup->getSettings()->clear();
            }
        }

        foreach ($newSettings as $settingArray) {
            $settingGroup = $settingArray[1];
            $setting = $settingArray[0];

            /*
             * Persist or not group
             */
            if (null !== $settingGroup) {
                if (!in_array($settingGroup->getName(), $groupsNames)) {
                    Kernel::getService('em')->persist($settingGroup);
                } else {
                    $settingGroup = Kernel::getService('em')
                        ->getRepository('RZ\Roadiz\Core\Entities\SettingGroup')
                        ->findOneByName($settingGroup->getName());

                }
            }
            /*
             * Add group to setting and persist if don't exist
             */
            $setting->setSettingGroup($settingGroup);
            if ($setting->getId() === null) {
                Kernel::getService('em')->persist($setting);
            }
        }
        $return = true;
        Kernel::getService('em')->flush();
        return $return;
    }
}
