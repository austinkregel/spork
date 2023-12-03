<?php
declare(strict_types=1);

namespace Feature\Operations;

use App\Models\Credential;
use App\Models\Server;
use App\Models\Spork\Script;
use App\Models\User;
use App\Operations\ServerAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerActionOperationTest extends TestCase
{
    use RefreshDatabase;

    public function testRunMethodCallAndErrorHandling()
    {
        $this->systemUnderTest = ServerAction::factory()->create([
            'server_id' => Server::factory()->create([
                'internal_ip_address' => '127.0.0.1'
            ])->id,
            'user_id' => User::factory(),
            'script_id' => Script::factory(),
            'credential_id' => Credential::factory()->create([
                'settings' => [
                    'pub_key_file' => '/var/www/html/tests/data/test_key.pub',
                    'pub_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQDLQp4/KjDp0QKlDrcTOkPUkQ7SVxZ90V1J0CRZ/FgZpWkAQp/5bt8wMiKBWfGOYdZxZTDKhTPheeXaLQoQp32DprhOA6SV5lGhD1CStFRVnASLBeJxP3k2MFIBH7LX5cql3Kf4mT8b55o1pZXLS3T0LG2PmYnsFDi2ilMbhSrmfecCYWtdZDFvO5WIlhntFvlYhb6DG233LzRCtpf+L/R1IZOSHVPf1d/fP+uc+EuW4lU5V5g+nhQqTlbyLJd02G3MX8J/6PrOJYOyvQ7sX86uooHozF7uPRqc8QfAhPcEnBhrHkUdQ8n/Vkyzwt7pVEXdW1TTKloXV/jrcfJx+jTLxIeaPtrSGrh3lnr4vg6Smwe+d3c/QftNKe8jWarWayJF3zTW8Hb3g9GUAE3YS29JmGUFxO5aeXVSL6lE0+O5BNYML4UIgmjQumAFjc0pG/fbLhZg/wI8DlQw4n6E8s6ExzoO9sGaIaXBXHXJgse/a0Xotly6Yil8aTyZLmiD4Z0= austinkregel@kratos',
                    'private_key_file' => '/var/www/html/tests/data/test_key',
                    'private_key' => '-----BEGIN OPENSSH PRIVATE KEY-----
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
-----END OPENSSH PRIVATE KEY-----',
                    'pass_key' => encrypt(''),
                    'username' => 'root',
                ]
            ])->id
        ]);

        $this->systemUnderTest->run();
    }
}
