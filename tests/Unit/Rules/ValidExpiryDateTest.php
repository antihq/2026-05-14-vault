<?php

use App\Rules\ValidExpiryDate;

test('valid expiry date passes', function () {
    $rule = new ValidExpiryDate;

    $passed = true;
    $rule->validate('expiry_date', '12/30', function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});

test('past expiry date fails', function () {
    $rule = new ValidExpiryDate;

    $failed = false;
    $rule->validate('expiry_date', '01/20', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('invalid month fails', function () {
    $rule = new ValidExpiryDate;

    $failed = false;
    $rule->validate('expiry_date', '13/28', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('invalid format fails', function () {
    $rule = new ValidExpiryDate;

    $failed = false;
    $rule->validate('expiry_date', '1228', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('current month is not expired', function () {
    $rule = new ValidExpiryDate;

    $now = \Carbon\Carbon::now();
    $value = $now->format('m/y');

    $passed = true;
    $rule->validate('expiry_date', $value, function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});
