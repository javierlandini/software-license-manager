<?php

uses()->group('integration');

test('ci verification integration test fails on purpose', function () {
    expect('integration-broken')->toBe('integration-ok');
});
