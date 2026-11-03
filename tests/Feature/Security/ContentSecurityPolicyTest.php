<?php

it('allows embedded videos only from the approved providers', function () {
    $this->get('/login')
        ->assertOk()
        ->assertHeader(
            'Content-Security-Policy',
            'frame-src https://www.youtube-nocookie.com https://player.vimeo.com',
        );
});
