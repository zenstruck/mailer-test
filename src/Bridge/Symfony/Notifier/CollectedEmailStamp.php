<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Bridge\Symfony\Notifier;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

/**
 * @internal
 */
final class CollectedEmailStamp implements NonSendableStampInterface
{
}
