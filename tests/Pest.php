<?php

declare(strict_types=1);

uses()->in('../packages/core/tests');

uses(Beacon\Recorder\Tests\TestCase::class)
    ->in('../packages/recorder/tests');

uses(Beacon\Dashboard\Tests\TestCase::class)
    ->in('../packages/dashboard/tests');
