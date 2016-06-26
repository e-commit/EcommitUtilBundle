<?php
/**
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Event;

class UpdateEvents
{
    /**
     * @Event("Ecommit\UtilBundle\Event\UpdateEvent")
     */
    const PRE_INSTALL = 'ecommit.update.pre_install';

    /**
     * @Event("Ecommit\UtilBundle\Event\UpdateEvent")
     */
    const POST_INSTALL = 'ecommit.update.post_install';

    /**
     * @Event("Ecommit\UtilBundle\Event\UpdateEvent")
     */
    const PRE_UPGRADE = 'ecommit.update.pre_upgrade';

    /**
     * @Event("Ecommit\UtilBundle\Event\UpdateEvent")
     */
    const POST_UPGRADE = 'ecommit.update.post_upgrade';
}
