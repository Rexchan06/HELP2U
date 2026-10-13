<?php

test('the home page redirects a guest to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});