<?php

it('redirects guests from the root page to login', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});
