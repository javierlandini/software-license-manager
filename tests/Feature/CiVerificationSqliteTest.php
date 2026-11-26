<?php

test('ci verification sqlite test fails on purpose', function () {
    expect('sqlite-broken')->toBe('sqlite-ok');
});
