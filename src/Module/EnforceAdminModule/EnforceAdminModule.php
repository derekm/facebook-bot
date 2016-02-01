<?php

/**
 * This file is part of the FacebookBot package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author  Derek Moore
 * @since   0.0.2
 * @version 0.0.4
 */

namespace PHPWorldWide\FacebookBot\Module\EnforceAdminModule;

use PHPWorldwide\FacebookBot\Connection\Connection;
use PHPWorldWide\FacebookBot\Module\ModuleAbstract;

/**
 * A member request handler that automatically approves new membership requests.
 */
class EnforceAdminModule extends ModuleAbstract
{
    const MEMBERLIST_PATH = '/groups/{group_id}/';

    public $debug = true;

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
        echo "polling admin data..." . PHP_EOL;
        $entities = [];
        $dom = new \DOMDocument();

        // get all members needing admin
        $page = $connection->request(Connection::REQ_LITE, self::MEMBERLIST_PATH, 'GET', [ 'view' => 'members' ]);
        $dom->loadHTML($page);
        $xpath = new \DOMXPath($dom);

        $addAdmins = array();
        do {
            $nodes = $xpath->evaluate("//a[text()='Make Admin']/@href");
            foreach ($nodes as $node) {
                array_push($addAdmins, $node->value);
            }
            $more = $xpath->evaluate("//div[@id='m_more_item']/a/@href");
            if ($more->length) {
                $page = $connection->request(Connection::REQ_LITE, $more->item(0)->value, 'GET');
                $dom->loadHTML($page);
                $xpath = new \DOMXPath($dom);
            }
        } while ($more->length);

        return $addAdmins;
    }

    /**
     * Handles a single membership request entity by admin'ing it.
     *
     * @param Connection $connection The connection to use for requests.
     * @param MemberRequestEntity $entity The entity to handle.
     *
     * @throws ConnectionException If something goes wrong with the connection.
     */
    protected function handleEntity(Connection $connection, $entity)
    {
        $page = $connection->request(Connection::REQ_LITE, $entity, 'GET');
        $dom = new \DOMDocument();
        $dom->loadHTML($page);
        $xpath = new \DOMXPath($dom);
        $form = $xpath->query("//form[@action='/a/group/add_admin/']");
        $form = $form->item(0);

        $inputs = $form->getElementsByTagName('input');
        $inputData = [ 'confirm' => 'Confirm' ];
        foreach ($inputs as $input) {
            if ($input->getAttribute('type') != 'submit') {
                $inputData[$input->getAttribute('name')] = $input->getAttribute('value');
            }
        }

        $connection->request(Connection::REQ_LITE, $form->getAttribute('action'), 'POST', $inputData);
    }
}
