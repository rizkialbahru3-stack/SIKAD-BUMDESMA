<?php

namespace Tests\Feature;

use Tests\TestCase;

class LogoutTest extends TestCase
{
    public function test_guest_post_logout_without_csrf_redirects_to_login_not_419(): void
    {
        // Mensimulasikan sesi kedaluwarsa / token CSRF basi: tanpa token sama sekali.
        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
    }

    public function test_guest_get_login_page_ok(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
