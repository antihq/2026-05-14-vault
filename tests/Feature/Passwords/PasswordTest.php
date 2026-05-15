<?php

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
        ->get(route('passwords.index', $team));

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
        ->get(route('passwords.index', $team));

    $response->assertOk();
    $response->assertSee('GitHub');
    $response->assertSee('johndoe');
});

test('password create page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.create', $team));

    $response->assertOk();
});

test('password can be created', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['team' => $team])
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

    Livewire::test('pages::passwords.create', ['team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->call('createPassword');

    $raw = \DB::table('passwords')->first();
    expect($raw->encrypted_password)->not->toBe('secret123');
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
        ->get(route('passwords.show', [$team, $password]));

    $response->assertOk();
    $response->assertSee('GitHub');
    $response->assertSee('johndoe');
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
        ->get(route('passwords.edit', [$team, $password]));

    $response->assertOk();
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
        ->call('deletePassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('passwords.index', $team));

    $this->assertDatabaseMissing('passwords', [
        'id' => $password->id,
    ]);
});

test('password cannot be accessed by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($nonMember)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertForbidden();
});

test('password generation works', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['team' => $team])
        ->call('generatePassword')
        ->assertSet('password', fn ($value) => strlen($value) === 16 && ! empty($value));
});

test('guests cannot access passwords', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this->get(route('passwords.index', $team));

    $response->assertRedirect(route('login'));
});

test('password creation requires name', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['team' => $team])
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

    Livewire::test('pages::passwords.create', ['team' => $team])
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

    Livewire::test('pages::passwords.create', ['team' => $team])
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

    Livewire::test('pages::passwords.create', ['team' => $team])
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

    Livewire::test('pages::passwords.create', ['team' => $team])
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

test('password creation redirects to show page', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->call('createPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('passwords.show', [$team, Password::first()]));
});

test('password update redirects to show page', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
        ->set('name', 'GitLab')
        ->set('username', 'janedoe')
        ->set('password', 'newsecret')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('passwords.show', [$team, $password]));
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'newsecret')
        ->call('updatePassword');

    $raw = \DB::table('passwords')->first();
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
        ->call('deletePassword')
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

    Livewire::test('pages::passwords.index', ['team' => $team])
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

    Livewire::test('pages::passwords.index', ['team' => $team])
        ->set('search', 'janedoe')
        ->assertSee('GitLab')
        ->assertDontSee('GitHub');
});

test('password show page displays website and notes', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
        'website' => 'https://github.com',
        'notes' => 'Personal account',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertOk();
    $response->assertSee('https://github.com');
    $response->assertSee('Notes');
});

test('password show page does not display delete button', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertDontSee('Delete password');
});

test('password show page embeds password value in alpine data', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertOk();
    $response->assertSee('secret123');
});

test('password show page safely encodes username with special characters', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'user"name</script>',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertOk();
    $response->assertDontSee('user"name</script>', false);
});

test('password show page safely encodes password with special characters', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'pass"word\'s</script>',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertOk();
    $response->assertDontSee("pass\"word's</script>", false);
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
        ->get(route('passwords.edit', [$team, $password]));

    $response->assertSee('Delete password');
});

test('password notes are encrypted', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'secret123')
        ->set('notes', 'Sensitive notes')
        ->call('createPassword');

    $raw = \DB::table('passwords')->first();
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

    $raw = \DB::table('passwords')->first();
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
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

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
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

    Livewire::test('pages::passwords.create', ['team' => $team])
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

    Livewire::test('pages::passwords.create', ['team' => $team])
        ->set('name', 'GitHub')
        ->set('username', str_repeat('a', 256))
        ->set('password', 'secret123')
        ->call('createPassword')
        ->assertHasErrors(['username' => 'max']);
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

    Livewire::test('pages::passwords.index', ['team' => $team])
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

test('password creation requires minimum 8 characters', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::passwords.create', ['team' => $team])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'short')
        ->call('createPassword')
        ->assertHasErrors(['password' => 'min']);
});

test('password show page displays timestamps', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertSee('Created');
    $response->assertSee('Updated');
});

test('password show page displays encryption status', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertSee('Encrypted at rest');
});

test('password show page displays team name', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('passwords.show', [$team, $password]));

    $response->assertSee($team->name);
});

test('password edit validates minimum 8 characters', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $password = $team->passwords()->create([
        'name' => 'GitHub',
        'username' => 'johndoe',
        'password' => 'secret123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::passwords.edit', ['team' => $team, 'password' => $password])
        ->set('name', 'GitHub')
        ->set('username', 'johndoe')
        ->set('password', 'short')
        ->call('updatePassword')
        ->assertHasErrors(['password' => 'min']);
});
