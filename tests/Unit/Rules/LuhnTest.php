<?php

use App\Rules\Luhn;

test('Luhn passes for valid Visa', function () {
    $rule = new Luhn;

    $passed = true;
    $rule->validate('card_number', '4242424242424242', function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});

test('Luhn passes for valid Mastercard', function () {
    $rule = new Luhn;

    $passed = true;
    $rule->validate('card_number', '5555555555554444', function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});

test('Luhn passes for valid Amex', function () {
    $rule = new Luhn;

    $passed = true;
    $rule->validate('card_number', '378282246310005', function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});

test('Luhn fails for invalid checksum', function () {
    $rule = new Luhn;

    $failed = false;
    $rule->validate('card_number', '4242424242424241', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('Luhn fails for number shorter than 13 digits', function () {
    $rule = new Luhn;

    $failed = false;
    $rule->validate('card_number', '42424242424', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('Luhn fails for number longer than 19 digits', function () {
    $rule = new Luhn;

    $failed = false;
    $rule->validate('card_number', '42424242424242424242', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('Luhn strips non-digit characters before validating', function () {
    $rule = new Luhn;

    $passed = true;
    $rule->validate('card_number', '4242-4242-4242-4242', function () use (&$passed) {
        $passed = false;
    });

    expect($passed)->toBeTrue();
});
