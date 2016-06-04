<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Two-factor TOTP
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\TwoFactorTotp\Provider;

use OCA\TwoFactorTotp\Exception\NoTotpSecretFoundException;
use OCA\TwoFactorTotp\Service\Totp;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IUser;
use OCP\Template;

class TotpProvider implements IProvider {

    /** @var Totp */
    private $totp;

    public function __construct(Totp $totp) {
        $this->totp = $totp;
    }

    /**
     * Get unique identifier of this 2FA provider
     *
     * @since 9.1.0
     *
     * @return string
     */
    public function getId() {
        return 'totp';
    }

    /**
     * Get the display name for selecting the 2FA provider
     *
     * Example: "Email"
     *
     * @since 9.1.0
     *
     * @return string
     */
    public function getDisplayName() {
        return 'TOTP (Google Authenticator)';
    }

    /**
     * Get the description for selecting the 2FA provider
     *
     * Example: "Get a token via e-mail"
     *
     * @since 9.1.0
     *
     * @return string
     */
    public function getDescription() {
        return 'Authenticate with a TOTP app';
    }

    /**
     * Get the template for rending the 2FA provider view
     *
     * @since 9.1.0
     *
     * @param IUser $user
     * @return Template
     */
    public function getTemplate(IUser $user) {
        try {
            $this->totp->getSecret($user);
        } catch (NoTotpSecretFoundException $ex) {
            $qr = $this->totp->createSecret($user);
        }

        $tmpl = new Template('twofactor_totp', 'challenge');
        $tmpl->assign('qr', $qr);
        return $tmpl;
    }

    /**
     * Verify the given challenge
     *
     * @since 9.1.0
     *
     * @param IUser $user
     * @param string $challenge
     */
    public function verifyChallenge(IUser $user, $challenge) {
        return $this->totp->validateSecret($user, $challenge);
    }

    /**
     * Decides whether 2FA is enabled for the given user
     *
     * @since 9.1.0
     *
     * @param IUser $user
     * @return boolean
     */
    public function isTwoFactorAuthEnabledForUser(IUser $user) {
        return true;
    }

}