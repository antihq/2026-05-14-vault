<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('vault index page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('vault.index', $team));

    $response->assertOk();
});

test('vault index shows passwords and credit cards', function () {
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

    Livewire::test('pages::vault.index', ['team' => $team])
        ->set('search', 'GitHub')
        ->assertSee('GitHub')
        ->set('search', 'My Visa')
        ->assertSee('My Visa');
});

test('vault index shows item types', function () {
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

    Livewire::test('pages::vault.index', ['team' => $team])
        ->set('search', 'GitHub')
        ->assertSee('Password');

    Livewire::test('pages::vault.index', ['team' => $team])
        ->set('search', 'My Visa')
        ->assertSee('Credit card');
});

test('vault index can be searched', function () {
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

    Livewire::test('pages::vault.index', ['team' => $team])
        ->set('search', 'GitHub')
        ->assertSee('GitHub')
        ->assertDontSee('My Visa');
});

test('vault index can be searched by username', function () {
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

    Livewire::test('pages::vault.index', ['team' => $team])
        ->set('search', 'johndoe')
        ->assertSee('GitHub')
        ->assertDontSee('My Visa');
});

test('vault index can be searched by name on card', function () {
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

    Livewire::test('pages::vault.index', ['team' => $team])
        ->set('search', 'John Doe')
        ->assertSee('My Visa')
        ->assertDontSee('GitHub');
});

test('vault index only shows items for the current team', function () {
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

    Livewire::test('pages::vault.index', ['team' => $team])
        ->assertSee('My GitHub')
        ->assertDontSee('Other GitHub');
});

test('vault index shows updated dates', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('vault.index', $team));

    $response->assertSee('Updated');
});

test('guests cannot access vault', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this->get(route('vault.index', $team));

    $response->assertRedirect(route('login'));
});

test('vault index shows key identifiers', function () {
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

    Livewire::test('pages::vault.index', ['team' => $team])
        ->set('search', 'johndoe')
        ->assertSee('johndoe');

    Livewire::test('pages::vault.index', ['team' => $team])
        ->set('search', 'My Visa')
        ->assertSee('4242');
});

test('vault index shows empty state when no items', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    Livewire::test('pages::vault.index', ['team' => $team])
        ->assertSee('No items in the vault yet.');
});
