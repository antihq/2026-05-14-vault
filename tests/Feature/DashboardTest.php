<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this->get(route('dashboard', $team));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertOk();
    $response->assertSee('Dashboard');
});

test('dashboard shows new password and new credit card buttons', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', $team));

    $response->assertSee('New password');
    $response->assertSee('New credit card');
});

test('dashboard shows zero password count when vault is empty', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('0 passwords');
});

test('dashboard shows zero credit card count when vault is empty', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('0 cards');
});

test('dashboard shows singular password count', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('1 password');
});

test('dashboard shows plural password count', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create(['name' => 'GitHub', 'username' => 'alice', 'password' => 'secret123']);
    $team->passwords()->create(['name' => 'GitLab', 'username' => 'bob', 'password' => 'secret456']);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('2 passwords');
});

test('dashboard shows singular credit card count', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('1 card');
});

test('dashboard shows plural credit card count', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $team->creditCards()->create([
        'name' => 'Amex',
        'name_on_card' => 'John Doe',
        'card_number' => '378282246310005',
        'expiry_date' => '06/27',
        'cvv' => '1234',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('2 cards');
});

test('dashboard shows recently viewed items with passwords and credit cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'last_viewed_at' => now(),
    ]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
        'last_viewed_at' => now(),
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('Recently viewed')
        ->assertSee('GitHub')
        ->assertSee('My Visa');
});

test('dashboard shows item type badges', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'last_viewed_at' => now(),
    ]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
        'last_viewed_at' => now(),
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('Password')
        ->assertSee('Credit card');
});

test('dashboard shows item key identifiers', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'last_viewed_at' => now(),
    ]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
        'last_viewed_at' => now(),
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('johndoe')
        ->assertSee('4242');
});

test('dashboard does not show recently viewed section when no items have been viewed', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertDontSee('Recently viewed');
});

test('dashboard does not show unviewed items in recently viewed section', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertDontSee('Recently viewed')
        ->assertDontSee('GitHub');
});

test('dashboard only shows recently viewed items for the current team', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($otherOwner, ['role' => TeamRole::Owner->value]);

    $team->passwords()->create([
        'name' => 'My GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'last_viewed_at' => now(),
    ]);

    $otherTeam->passwords()->create([
        'name' => 'Other GitHub',
        'username' => 'otheruser',
        'password' => 'secret456',
        'last_viewed_at' => now(),
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('My GitHub')
        ->assertDontSee('Other GitHub');
});

test('dashboard shows expired cards with expired badge', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'Expired Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '01/20',
        'cvv' => '123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('Card expiry')
        ->assertSee('Expired Card')
        ->assertSee('Expired');
});

test('dashboard shows expiring soon cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $expiryDate = now()->addDays(30)->format('m/y');

    $team->creditCards()->create([
        'name' => 'Expiring Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => $expiryDate,
        'cvv' => '123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('Card expiry')
        ->assertSee('Expiring Card')
        ->assertSee('Expiring soon');
});

test('dashboard does not show card expiry section when no cards have issues', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'Valid Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertDontSee('Card expiry');
});

test('dashboard does not show far future cards as expiring soon', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'Future Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/29',
        'cvv' => '123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertDontSee('Card expiry')
        ->assertDontSee('Expiring soon');
});

test('dashboard recently viewed items are sorted by last_viewed_at descending', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'Oldest',
        'username' => 'oldest',
        'password' => 'secret123',
        'last_viewed_at' => now()->subHours(2),
    ]);

    $team->passwords()->create([
        'name' => 'Newest',
        'username' => 'newest',
        'password' => 'secret456',
        'last_viewed_at' => now(),
    ]);

    $team->passwords()->create([
        'name' => 'Middle',
        'username' => 'middle',
        'password' => 'secret789',
        'last_viewed_at' => now()->subHour(),
    ]);

    $html = Livewire::test('pages::dashboard', ['current_team' => $team])->html();

    $newestPos = strpos($html, 'Newest');
    $middlePos = strpos($html, 'Middle');
    $oldestPos = strpos($html, 'Oldest');

    expect($newestPos)->toBeLessThan($middlePos);
    expect($middlePos)->toBeLessThan($oldestPos);
});

test('dashboard shows both expired and expiring soon cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'Expired Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '01/20',
        'cvv' => '123',
    ]);

    $team->creditCards()->create([
        'name' => 'Expiring Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424243',
        'expiry_date' => now()->addDays(30)->format('m/y'),
        'cvv' => '456',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('Expired Card')
        ->assertSee('Expired')
        ->assertSee('Expiring Card')
        ->assertSee('Expiring soon');
});

test('dashboard shows expired cards before expiring soon cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'Expiring Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424243',
        'expiry_date' => now()->addDays(30)->format('m/y'),
        'cvv' => '456',
    ]);

    $team->creditCards()->create([
        'name' => 'Expired Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '01/20',
        'cvv' => '123',
    ]);

    $html = Livewire::test('pages::dashboard', ['current_team' => $team])->html();

    $expiredPos = strpos($html, 'Expired Card');
    $expiringPos = strpos($html, 'Expiring Card');

    expect($expiredPos)->toBeLessThan($expiringPos);
});
