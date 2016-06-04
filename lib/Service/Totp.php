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

namespace OCA\TwoFactorTotp\Service;

use Base32\Base32;
use OCA\TwoFactorTotp\Db\TotpSecret;
use OCA\TwoFactorTotp\Db\TotpSecretMapper;
use OCA\TwoFactorTotp\Exception\NoTotpSecretFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUser;
use OCP\Security\ICrypto;
use Otp\GoogleAuthenticator;
use Otp\Otp;

class Totp implements ITotp {

    /** @var TotpSecretMapper */
    private $secretMapper;

    /** @var ICrypto */
    private $crypto;

    public function __construct(TotpSecretMapper $secretMapper, ICrypto $crypto) {
        $this->secretMapper = $secretMapper;
        $this->crypto = $crypto;
    }

    public function getSecret(IUser $user) {
        try {
            $secret = $this->secretMapper->getSecret($user);
        } catch (DoesNotExistException $ex) {
            throw new NoTotpSecretFoundException();
        }

        $encryptedSecret = $secret->getSecret();
        return $this->crypto->decrypt($encryptedSecret);
    }

    /**
     * @todo prevent duplicates
     * 
     * @param IUser $user
     */
    public function createSecret(IUser $user) {
        $secret = GoogleAuthenticator::generateRandom();

        $dbSecret = new TotpSecret();
        $dbSecret->setUserId($user->getUID());
        $dbSecret->setSecret($this->crypto->encrypt($secret));

        $this->secretMapper->insert($dbSecret);

        return GoogleAuthenticator::getQrCodeUrl('totp', 'ownCloud TOTP', $secret);
    }

    public function validateSecret(IUser $user, $key) {
        try {
            $dbSecret = $this->secretMapper->getSecret($user);
        } catch (DoesNotExistException $ex) {
            throw new NoTotpSecretFoundException();
        }

        $secret = $this->crypto->decrypt($dbSecret->getSecret());

        $otp = new Otp();
        return $otp->checkTotp(Base32::decode($secret), $key, 3);
    }

}
