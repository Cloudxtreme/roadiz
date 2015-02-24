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
 * @file Logger.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Log;

use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * A log system which store message in database.
 */
class Logger implements LoggerInterface
{
    /**
     * @var Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext = null;
    /**
     * @return Symfony\Component\Security\Core\SecurityContextInterface
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }
    /**
     * @param Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     *
     * @return $this
     */
    public function setSecurityContext(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;

        return $this;
    }

    private $user = null;

    /**
     * @return RZ\Roadiz\Core\Entities\User
     */
    public function getUser() {
        return $this->user;
    }
    /**
     * @param RZ\Roadiz\Core\Entities\User $user
     */
    public function setUser(User $user) {
        $this->user = $user;

        return $this;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     */
    public function emergency($message, array $context = [], NodesSources $source = null)
    {
        $this->log(Log::EMERGENCY, $message, $context, $source);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     */
    public function alert($message, array $context = [], NodesSources $source = null)
    {
        $this->log(Log::ALERT, $message, $context, $source);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     */
    public function critical($message, array $context = [], NodesSources $source = null)
    {
        $this->log(Log::CRITICAL, $message, $context, $source);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     */
    public function error($message, array $context = [], NodesSources $source = null)
    {
        $this->log(Log::ERROR, $message, $context, $source);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     */
    public function warning($message, array $context = [], NodesSources $source = null)
    {
        $this->log(Log::WARNING, $message, $context, $source);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     */
    public function notice($message, array $context = [], NodesSources $source = null)
    {
        $this->log(Log::NOTICE, $message, $context, $source);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     */
    public function info($message, array $context = [], NodesSources $source = null)
    {
        $this->log(Log::INFO, $message, $context, $source);
    }

    /**
     * Detailed debug information is desactivated not to flood Log table.
     *
     * @param string $message
     * @param array  $context
     */
    public function debug($message, array $context = [], NodesSources $source = null)
    {
        return;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     * @param RZ\Roadiz\Core\Entities\NodesSources $source
     */
    public function log($level, $message, array $context = [], NodesSources $source = null)
    {
        if (Kernel::getService('em')->isOpen()) {
            $log = new Log($level, $message, $context);

            /*
             * Use available securityContext to provide a valid user
             */
            if (null !== $this->getSecurityContext() &&
                null !== $this->getSecurityContext()->getToken() &&
                is_object($this->getSecurityContext()->getToken()->getUser()) &&
                null !== $this->getSecurityContext()->getToken()->getUser()->getId()) {
                $log->setUser($this->getSecurityContext()->getToken()->getUser());
            }
            /*
             * Use manually set user
             */
            if (null !== $this->getUser()) {
                $log->setUser($this->getUser());
            }

            /*
             * Add client IP to log if it’s an HTTP request
             */
            if (null !== Kernel::getInstance()->getRequest()) {
                $log->setClientIp(Kernel::getInstance()->getRequest()->getClientIp());
            }

            /*
             * Add a related node-source entity
             */
            if (null !== $source) {
                $log->setNodeSource($source);
            }

            Kernel::getService('em')->persist($log);
            Kernel::getService('em')->flush();
        }
    }
}
