<?php

/*
 * This file is part of the zenstruck/mailer-test package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Mailer\Test\Expectation;

use Zenstruck\Assert\HtmlExpectation as BaseHtmlExpectation;
use Zenstruck\Mailer\Test\TestEmail;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HtmlExpectation extends BaseHtmlExpectation
{
    public function __construct(string $html, private TestEmail $email)
    {
        parent::__construct($html);
    }

    public function back(): TestEmail
    {
        return $this->email;
    }
}
