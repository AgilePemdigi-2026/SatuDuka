<?php

test('halaman awal mengalihkan tamu ke halaman masuk', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('masuk'));
});
