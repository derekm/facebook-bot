<?php

/**
 * This file is part of the FacebookBot package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author  Peter Kokot
 * @author  Dennis Degryse
 * @since   0.0.2
 * @version 0.0.4
 */

namespace PHPWorldWide\FacebookBot\Module\MemberRequestModule;

use PHPWorldwide\FacebookBot\Connection\Connection;
use PHPWorldWide\FacebookBot\Module\ModuleAbstract;

/**
 * A member request handler that automatically approves new membership requests.
 */
class MemberRequestModule extends ModuleAbstract
{
    const MEMBERLIST_PATH = '/groups/{group_id}/';
    const REQUESTFORM_ACTION = '/write_async/requests/';

    public $debug = false;

    /**
     * Fetches the list of membership request entities.
     *
     * @param Connection $connection The connection to use for requests.
     *
     * @return array The list of membership request entities.
     *
     * @throws ConnectionException If something goes wrong with the connection.
     */
    protected function pollData(Connection $connection)
    {
        $entities = [];
        $dom = new \DOMDocument();

        $page = $connection->request(Connection::REQ_LITE, self::MEMBERLIST_PATH, 'GET', [ 'view' => 'requests' ]);
        $dom->loadHTML($page);

        $forms = $dom->getElementsByTagName('form');

        foreach ($forms as $form) {
            if (strpos($form->getAttribute('action'), self::REQUESTFORM_ACTION) !== FALSE) {
                $entities[] = $this->parseEntity($form);
            }
        }

        return $entities;
    }

    /**
     * Handles a single membership request entity by approving it.
     *
     * @param Connection $connection The connection to use for requests.
     * @param MemberRequestEntity $entity The entity to handle.
     *
     * @throws ConnectionException If something goes wrong with the connection.
     */
    protected function handleEntity(Connection $connection, $entity)
    {
        $connection->request(Connection::REQ_LITE, $entity->getActionUrl(), 'POST', $entity->getInputData());
    }

    /**
     * Parses the given HTML Form element and produces a representative membership request entity.
     *
     * @param DOMElement $form The HTML Form element.
     *
     * @return MemberRequestEntity The membership request entity.
     */
    private function parseEntity(\DOMElement $form)
    {
        $inputElements = $form->getElementsByTagName('input');

        $actionUrl = $form->getAttribute('action');
        $inputData = [ 'submit' => 'Approve All' ];

        foreach ($inputElements as $input) {
            if ($input->getAttribute('type') != 'submit') {
                $inputData[$input->getAttribute('name')] = $input->getAttribute('value');
            }
        }

        return new MemberRequestEntity(null, null, $actionUrl, $inputData);
    }

    /**
     * Sanitizes the form action URL.
     *
     * @param string $url The action URL to sanitize.
     *
     * @return string The fully qualified action URL
     */
    private function sanitizeActionUrl($url) {
        preg_match("%^(https?\://.*\.facebook\.com|)(/.*)$%", $url, $match);

        $path = $match[2];

        return $path;
    }

    /**
     * Sanitizes the profile URL.
     *
     * @param string $url The profile URL to sanitize.
     *
     * @return string The fully qualified context-free profile URL.
     */
    private function sanitizeProfileUrl($url) {
        if(preg_match("%^(https?\://.*\.facebook\.com|)/?(profile.php\?id=[0-9]+|[^/\?]+)%", $url, $match)) {
            $facebookId = $match[2];
        } else {
            $facebookId = 0;
        }

        return "https://www.facebook.com/$facebookId";
    }
}
