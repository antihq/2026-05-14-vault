<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Password;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('passwords index page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.index', ['current_team' => $team]));

    $response->assertOk();
});

test('passwords index shows passwords', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.index', ['current_team' => $team]));

    $response->assertOk();
    $response->assertSee('GitHub');
    $response->assertSee('johndoe');
});

test('password create page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.create', ['current_team' => $team]));

    $response->assertOk();
});

test('password can be created', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->call('createPassword')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('passwords', [
        'team_id' => $team->id,
        'name' => 'GitHub',
        'username' => 'johndoe',
    ]);

    $password = Password::first();
    expect($password->password)->toBe('secret123');
});

test('password creation encrypts the password', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->call('createPassword');

    $raw = DB::table('passwords')->first();
    expect($raw->encrypted_password)->not->toBe('secret123');
});

test('password edit page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.edit', ['current_team' => $team, 'password' => $password]));

    $response->assertOk();
});

test('password show page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', ['current_team' => $team, 'password' => $password]));

    $response->assertOk();
    $response->assertSee('GitHub');
    $response->assertSee('johndoe');
});

test('password show page records last viewed timestamp', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    expect($password->last_viewed_at)->toBeNull();

    $this
        ->actingAs($user)
        ->get(route('passwords.show', ['current_team' => $team, 'password' => $password]));

    expect($password->fresh()->last_viewed_at)->not->toBeNull();
});

test('password show page cannot be viewed by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($nonMember);

    Livewire::test('pages::passwords.show', ['current_team' => $team, 'password' => $password])
        ->assertForbidden();
});

test('password can be updated', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->set('name', 'GitLab')
        ->set('username', 'janedoe')
        ->set('password', 'newsecret')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('passwords', [
        'id' => $password->id,
        'name' => 'GitLab',
        'username' => 'janedoe',
    ]);

    expect($password->fresh()->password)->toBe('newsecret');
});

test('password can be deleted', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->call('deletePassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('passwords.index', ['current_team' => $team]));

    $this->assertDatabaseMissing('passwords', [
        'id' => $password->id,
    ]);
});

test('password create page auto-generates a password on load', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->assertSet('password', fn ($value) => strlen($value) === 16 && ! empty($value));
});

test('password generation works', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->call('generatePassword')
        ->assertSet('password', fn ($value) => strlen($value) === 16 && ! empty($value));
});

test('guests cannot access passwords', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this->get(route('passwords.index', ['current_team' => $team]));

    $response->assertRedirect(route('login'));
});

test('password creation requires name', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', '')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->call('createPassword')
        ->assertHasErrors(['name' => 'required']);
});

test('password creation requires username', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', 'GitHub')
        ->set('username', '')
        ->set('password', 'secret123')
        ->call('createPassword')
        ->assertHasErrors(['username' => 'required']);
});

test('password creation requires password', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', '')
        ->call('createPassword')
        ->assertHasErrors(['password' => 'required']);
});

test('password creation validates website as url', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->set('website', 'not-a-url')
        ->call('createPassword')
        ->assertHasErrors(['website' => 'url']);
});

test('password can be created with all optional fields', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->set('website', 'https://github.com')
        ->set('notes', 'Personal account')
        ->call('createPassword')
        ->assertHasNoErrors();

    $password = Password::first();
    expect($password->website)->toBe('https://github.com');
    expect($password->notes)->toBe('Personal account');
});

test('password update encrypts the new password', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'newsecret')
        ->call('updatePassword');

    $raw = DB::table('passwords')->first();
    expect($raw->encrypted_password)->not->toBe('newsecret');
    expect($raw->encrypted_password)->not->toBe(encrypt('secret123'));
});

test('password cannot be updated by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($nonMember);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->set('name', 'Hacked')
        ->set('username', 'hacked')
        ->set('password', 'hacked')
        ->call('updatePassword')
        ->assertForbidden();
});

test('password cannot be deleted by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($nonMember);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->call('deletePassword')
        ->assertForbidden();

    $this->assertDatabaseHas('passwords', ['id' => $password->id]);
});

test('password can be deleted from index page', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.index', ['current_team' => $team])
        ->call('deletePassword', $password->id)
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertDatabaseMissing('passwords', [
        'id' => $password->id,
    ]);
});

test('password cannot be deleted from index page by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($nonMember);

    Livewire::test('pages::passwords.index', ['current_team' => $team])
        ->call('deletePassword', $password->id)
        ->assertForbidden();

    $this->assertDatabaseHas('passwords', ['id' => $password->id]);
});

test('passwords index can be searched by name', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $team->passwords()->create([
        'name' => 'GitLab',
        'username' => 'janedoe',
        'password' => 'secret456',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.index', ['current_team' => $team])
        ->set('search', 'GitLab')
        ->assertSee('GitLab')
        ->assertDontSee('GitHub');
});

test('passwords index can be searched by username', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $team->passwords()->create([
        'name' => 'GitLab',
        'username' => 'janedoe',
        'password' => 'secret456',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.index', ['current_team' => $team])
        ->set('search', 'janedoe')
        ->assertSee('GitLab')
        ->assertDontSee('GitHub');
});

test('password edit page displays delete button', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.edit', ['current_team' => $team, 'password' => $password]));

    $response->assertSee('Delete password');
});

test('password notes are encrypted', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->set('notes', 'Sensitive notes')
        ->call('createPassword');

    $raw = DB::table('passwords')->first();
    expect($raw->encrypted_notes)->not->toBe('Sensitive notes');

    $password = Password::first();
    expect($password->notes)->toBe('Sensitive notes');
});

test('password notes can be set to null', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'notes' => 'Some notes',
    ]);

    expect($password->notes)->toBe('Some notes');

    $password->update(['notes' => null]);

    $raw = DB::table('passwords')->first();
    expect($raw->encrypted_notes)->toBeNull();

    expect($password->fresh()->notes)->toBeNull();
});

test('password edit page pre-fills with decrypted values', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'notes' => 'My notes',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->assertSet('password', 'secret123')
        ->assertSet('notes', 'My notes');
});

test('password edit validates name required', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->set('name', '')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->call('updatePassword')
        ->assertHasErrors(['name' => 'required']);
});

test('password edit validates username required', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->set('name', 'GitHub')
        ->set('username', '')
        ->set('password', 'secret123')
        ->call('updatePassword')
        ->assertHasErrors(['username' => 'required']);
});

test('password edit validates password required', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', '')
        ->call('updatePassword')
        ->assertHasErrors(['password' => 'required']);
});

test('password creation validates name max length', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', str_repeat('a', 256))
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->call('createPassword')
        ->assertHasErrors(['name' => 'max']);
});

test('password creation validates username max length', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->set('name', 'GitHub')
        ->set('username', str_repeat('a', 256))
        ->set('password', 'secret123')
        ->call('createPassword')
        ->assertHasErrors(['username' => 'max']);
});

test('passwords search is scoped to the current team', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($otherOwner, ['role' => TeamRole::Owner->value]);

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $otherTeam->passwords()->create([
        'name' => 'Leaked',
        'username' => 'johndoe',
        'password' => 'secret456',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.index', ['current_team' => $team])
        ->set('search', 'johndoe')
        ->assertSee('GitHub')
        ->assertDontSee('Leaked');
});

test('passwords index only shows passwords for the current team', function () {
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

    $this->actingAs($user);

    Livewire::test('pages::passwords.index', ['current_team' => $team])
        ->assertSee('My GitHub')
        ->assertDontSee('Other GitHub');
});

test('passwords are deleted when team is force deleted', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['is_personal' => false]);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    expect(Password::count())->toBe(1);

    $team->forceDelete();

    expect(Password::count())->toBe(0);
});

test('username suggestions include past usernames from the team', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create(['name' => 'GitHub', 'username' => 'alice@example.com', 'password' => 'secret123']);
    $team->passwords()->create(['name' => 'GitLab', 'username' => 'bob@example.com', 'password' => 'secret456']);

    $this->actingAs($user);

    $suggestions = Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->get('usernameSuggestions');

    expect($suggestions)->toContain('alice@example.com');
    expect($suggestions)->toContain('bob@example.com');
});

test('username suggestions are deduplicated', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create(['name' => 'GitHub', 'username' => 'alice@example.com', 'password' => 'secret123']);
    $team->passwords()->create(['name' => 'GitLab', 'username' => 'alice@example.com', 'password' => 'secret456']);
    $team->passwords()->create(['name' => 'Bitbucket', 'username' => 'alice@example.com', 'password' => 'secret789']);

    $this->actingAs($user);

    $suggestions = Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->get('usernameSuggestions');

    expect($suggestions)->toHaveCount(1);
    expect($suggestions)->toContain('alice@example.com');
});

test('username suggestions are empty when team has no passwords', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    $suggestions = Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->get('usernameSuggestions');

    expect($suggestions)->toHaveCount(0);
});

test('username suggestions are scoped to the current team', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($otherOwner, ['role' => TeamRole::Owner->value]);

    $team->passwords()->create(['name' => 'GitHub', 'username' => 'alice@example.com', 'password' => 'secret123']);
    $otherTeam->passwords()->create(['name' => 'Other', 'username' => 'bob@example.com', 'password' => 'secret456']);

    $this->actingAs($user);

    $suggestions = Livewire::test('pages::passwords.create', ['current_team' => $team])
        ->get('usernameSuggestions');

    expect($suggestions)->toContain('alice@example.com');
    expect($suggestions)->not->toContain('bob@example.com');
});

test('create page renders autocomplete with username suggestions', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create(['name' => 'GitHub', 'username' => 'alice@example.com', 'password' => 'secret123']);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.create', ['current_team' => $team]));

    $response->assertOk();
    $response->assertSee('alice@example.com');
});

test('edit page username suggestions include past usernames from the team', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create(['name' => 'GitHub', 'username' => 'alice@example.com', 'password' => 'secret123']);
    $team->passwords()->create(['name' => 'GitLab', 'username' => 'bob@example.com', 'password' => 'secret456']);

    $password = $team->passwords()->create(['name' => 'Bitbucket', 'username' => 'charlie@example.com', 'password' => 'secret789']);

    $this->actingAs($user);

    $suggestions = Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->get('usernameSuggestions');

    expect($suggestions)->toContain('alice@example.com');
    expect($suggestions)->toContain('bob@example.com');
    expect($suggestions)->toContain('charlie@example.com');
});

test('edit page renders autocomplete with username suggestions', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create(['name' => 'GitHub', 'username' => 'alice@example.com', 'password' => 'secret123']);

    $password = $team->passwords()->create(['name' => 'GitLab', 'username' => 'bob@example.com', 'password' => 'secret456']);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.edit', ['current_team' => $team, 'password' => $password]));

    $response->assertOk();
    $response->assertSee('alice@example.com');
});

test('password generation works on edit page', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $team, 'password' => $password])
        ->call('generatePassword')
        ->assertSet('password', fn ($value) => strlen($value) === 16 && ! empty($value));
});

test('passwords index shows domain instead of full URL', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'website' => 'https://www.github.com/settings',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.index', ['current_team' => $team]));

    $response->assertSee('www.github.com');
    $response->assertSee('href="https://www.github.com/settings"', false);
});

test('team owner can move a password to another team they belong to', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $destination = Team::factory()->create();
    $source->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $destination->members()->attach($user, ['role' => TeamRole::Member->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $destination->id)
        ->call('movePassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('passwords.show', ['current_team' => $destination, 'password' => $password]));

    expect($password->fresh()->team_id)->toBe($destination->id);
});

test('team admin can move a password to another team', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $destination = $user->personalTeam();
    $source->members()->attach($user, ['role' => TeamRole::Admin->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $destination->id)
        ->call('movePassword')
        ->assertHasNoErrors();

    expect($password->fresh()->team_id)->toBe($destination->id);
});

test('regular member cannot move a password out of the team', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $destination = $user->personalTeam();
    $source->members()->attach($user, ['role' => TeamRole::Member->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $destination->id)
        ->call('movePassword')
        ->assertForbidden();

    expect($password->fresh()->team_id)->toBe($source->id);
});

test('non team member cannot move a password', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $source = Team::factory()->create();
    $source->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($nonMember);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $nonMember->personalTeam()->id)
        ->call('movePassword')
        ->assertForbidden();

    expect($password->fresh()->team_id)->toBe($source->id);
});

test('password cannot be moved to a team the user does not belong to', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $source->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $otherTeam->id)
        ->call('movePassword')
        ->assertHasErrors(['moveToTeamId']);

    expect($password->fresh()->team_id)->toBe($source->id);
});

test('password cannot be moved to the same team it already belongs to', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $source->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $source->id)
        ->call('movePassword')
        ->assertHasErrors(['moveToTeamId']);
});

test('moving a password does not re encrypt the value and it stays decryptable', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $destination = $user->personalTeam();
    $source->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'notes' => 'sensitive notes',
    ]);

    $originalEncryptedPassword = DB::table('passwords')->where('id', $password->id)->value('encrypted_password');
    $originalEncryptedNotes = DB::table('passwords')->where('id', $password->id)->value('encrypted_notes');

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $destination->id)
        ->call('movePassword')
        ->assertHasNoErrors();

    $raw = DB::table('passwords')->where('id', $password->id)->first();

    expect($raw->encrypted_password)->toBe($originalEncryptedPassword);
    expect($raw->encrypted_notes)->toBe($originalEncryptedNotes);

    $fresh = $password->fresh();
    expect($fresh->password)->toBe('secret123');
    expect($fresh->notes)->toBe('sensitive notes');
});

test('password can be moved into the personal team', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $personal = $user->personalTeam();
    $source->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $personal->id)
        ->call('movePassword')
        ->assertHasNoErrors();

    expect($password->fresh()->team_id)->toBe($personal->id);
});

test('password can be moved out of the personal team', function () {
    $user = User::factory()->create();
    $personal = $user->personalTeam();
    $destination = Team::factory()->create();
    $destination->members()->attach($user, ['role' => TeamRole::Member->value]);

    $password = $personal->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $personal, 'password' => $password])
        ->set('moveToTeamId', $destination->id)
        ->call('movePassword')
        ->assertHasNoErrors();

    expect($password->fresh()->team_id)->toBe($destination->id);
});

test('edit page shows move section for team admins with another team', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $source->members()->attach($user, ['role' => TeamRole::Admin->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.edit', ['current_team' => $source, 'password' => $password]));

    $response->assertOk();
    $response->assertSee('Move to team');
    $response->assertSee($user->personalTeam()->name);
});

test('edit page hides move section for regular members', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $source->members()->attach($user, ['role' => TeamRole::Member->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.edit', ['current_team' => $source, 'password' => $password]));

    $response->assertOk();
    $response->assertDontSee('Move to team');
});

test('edit page hides move section when user has no other team to move to', function () {
    $user = User::factory()->create();
    $personal = $user->personalTeam();

    $password = $personal->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.edit', ['current_team' => $personal, 'password' => $password]));

    $response->assertOk();
    $response->assertDontSee('Move to team');
});

test('move permission is granted to admin role', function () {
    expect(TeamRole::Admin->hasPermission(TeamPermission::MovePassword))->toBeTrue();
});

test('move permission is granted to owner role', function () {
    expect(TeamRole::Owner->hasPermission(TeamPermission::MovePassword))->toBeTrue();
});

test('move permission is not granted to member role', function () {
    expect(TeamRole::Member->hasPermission(TeamPermission::MovePassword))->toBeFalse();
});

test('moved password becomes inaccessible to source team members and accessible to destination team members', function () {
    $mover = User::factory()->create();
    $sourceMember = User::factory()->create();
    $destinationMember = User::factory()->create();

    $source = Team::factory()->create();
    $destination = Team::factory()->create();

    $source->members()->attach($mover, ['role' => TeamRole::Owner->value]);
    $source->members()->attach($sourceMember, ['role' => TeamRole::Member->value]);
    $destination->members()->attach($mover, ['role' => TeamRole::Owner->value]);
    $destination->members()->attach($destinationMember, ['role' => TeamRole::Member->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($sourceMember)
        ->get(route('passwords.show', ['current_team' => $source, 'password' => $password]))
        ->assertOk();

    $this->actingAs($mover);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->set('moveToTeamId', $destination->id)
        ->call('movePassword')
        ->assertHasNoErrors();

    $this->actingAs($sourceMember)
        ->get(route('passwords.show', ['current_team' => $source, 'password' => $password]))
        ->assertForbidden();

    $this->actingAs($destinationMember)
        ->get(route('passwords.show', ['current_team' => $destination, 'password' => $password]))
        ->assertOk()
        ->assertSee('GitHub');
});

test('movePassword requires a destination team to be selected', function () {
    $user = User::factory()->create();
    $source = $user->personalTeam();

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->call('movePassword')
        ->assertHasErrors(['moveToTeamId']);

    expect($password->fresh()->team_id)->toBe($source->id);
});

test('movePassword auto-selects the only available destination team', function () {
    $user = User::factory()->create();
    $source = Team::factory()->create();
    $destination = $user->personalTeam();
    $source->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->call('movePassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('passwords.show', ['current_team' => $destination, 'password' => $password]));

    expect($password->fresh()->team_id)->toBe($destination->id);
});

test('movableTeams excludes the current team and includes all other teams the user belongs to', function () {
    $user = User::factory()->create();
    $personal = $user->personalTeam();
    $source = Team::factory()->create();
    $other = Team::factory()->create();

    $source->members()->attach($user, ['role' => TeamRole::Admin->value]);
    $other->members()->attach($user, ['role' => TeamRole::Member->value]);

    $password = $source->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    $movable = Livewire::test('pages::passwords.edit', ['current_team' => $source, 'password' => $password])
        ->get('movableTeams');

    expect($movable->pluck('id')->sort()->values()->all())
        ->toBe(collect([$personal->id, $other->id])->sort()->values()->all())
        ->and($movable->pluck('id'))->not->toContain($source->id);
});
