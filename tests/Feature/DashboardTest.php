<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
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

test('dashboard shows recent items with passwords and credit cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('Recent items')
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
    ]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
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
    ]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('johndoe')
        ->assertSee('4242');
});

test('dashboard does not show recent items section when vault is empty', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertDontSee('Recent items');
});

test('dashboard only shows items for the current team', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($otherOwner, ['role' => TeamRole::Owner->value]);

    $team->passwords()->create([
        'name' => 'My GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $otherTeam->passwords()->create([
        'name' => 'Other GitHub',
        'username' => 'otheruser',
        'password' => 'secret456',
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

test('dashboard shows pending invitations', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invitee@example.com',
        'invited_by' => $user->id,
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertSee('Pending invitations')
        ->assertSee('invitee@example.com');
});

test('dashboard does not show accepted invitations', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'accepted@example.com',
        'invited_by' => $user->id,
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertDontSee('accepted@example.com');
});

test('dashboard does not show expired invitations', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'expired@example.com',
        'invited_by' => $user->id,
    ]);

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertDontSee('expired@example.com');
});

test('dashboard does not show pending invitations section when none exist', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    Livewire::test('pages::dashboard', ['current_team' => $team])
        ->assertDontSee('Pending invitations');
});
