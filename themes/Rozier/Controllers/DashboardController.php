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
 * @file DashboardController.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Controllers;

use Doctrine\Common\Collections\Criteria;
use RZ\Roadiz\Core\Entities\Log;
use Symfony\Component\HttpFoundation\Request;
use Themes\Rozier\RozierApp;

/**
 * Main backoffice entrance.
 */
class DashboardController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response $response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_BACKEND_USER');

        $this->assignation['latestLogs'] = [];

        $logs = $this->get('em')
             ->getRepository('RZ\Roadiz\Core\Entities\Log')
             ->findLatestByNodesSources(8);

        $criteria = Criteria::create()
            ->orderBy(["datetime" => Criteria::DESC])
            ->setFirstResult(0)
            ->setMaxResults(1);

        /*
         * Ensure that we really get latest log for
         * given nodeSource because of GROUP BY sql command.
         */
        /** @var Log $log */
        foreach ($logs as $log) {
            $nodeSource = $log->getNodeSource();
            $this->assignation['latestLogs'][] = $nodeSource->getLogs()->matching($criteria)->get(0);
        }

        return $this->render('dashboard/index.html.twig', $this->assignation);
    }
}
