<?php

test('the application redirects to support requests', function () {
    $response = $this->get('/');

    $response->assertRedirect('/support-requests');
});
