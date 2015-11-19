<?php

/**
 * This file is part of the FacebookBot package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author  Peter Kokot
 * @author  Dennis Degryse
 * @since   0.0.4
 * @version 0.0.4
 */

namespace PHPWorldWide\FacebookBot\Connection\SessionBuilder;

use PHPWorldWide\FacebookBot\Connection\Request\CURLRequest;

/**
 * An adapter for Facebook Graph session builders.
 */
class FacebookSessionBuilder implements SessionBuilder
{
    const REQ_BASEURL = 'https://www.facebook.com';
    const REQ_PATH = '/login.php';

    /**
     * The login e-mail.
     */
    private $email;

    /**
     * The login password.
     */
    private $password;

    /**
     * Creates a new instance.
     *
     * @param string $email The login e-mail.
     * @param string $password The login password.
     */
    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function build()
    {
        $request = new CURLRequest(self::REQ_BASEURL, self::REQ_PATH, 'GET');
        $result = $request->execute();
        $dom = new \DOMDocument();
        $dom->loadHTML($result);

        $form = $dom->getElementById('login_form');
        if (!$form) {
            return array();
        }
        $inputs = $form->getElementsByTagName('input');

        foreach ($inputs as $input) {
            if ($input->getAttribute('type') != 'submit') {
                $data[$input->getAttribute('name')] = $input->getAttribute('value');
            }
        }

        $data['email'] = $this->email;
        $data['pass'] = $this->password;

        // TODO FIXME it fails here when logging in
        $request = new CURLRequest(self::REQ_BASEURL, $form->getAttribute('action'), 'POST', null, $data, true);
        $result = $request->execute();

        return array();
    }
}