<?php

use App\Enums\TeamRole;
use App\Models\CreditCard;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

test('credit cards index page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.index', $team));

    $response->assertOk();
});

test('credit cards index shows credit cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.index', $team));

    $response->assertOk();
    $response->assertSee('My Visa');
    $response->assertSee('4242');
});

test('credit card create page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.create', $team));

    $response->assertOk();
});

test('credit card can be created', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('credit_cards', [
        'team_id' => $team->id,
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'last_four' => '4242',
        'expiry_date' => '12/28',
    ]);

    $creditCard = CreditCard::first();
    expect($creditCard->card_number)->toBe('4242424242424242');
    expect($creditCard->cvv)->toBe('123');
});

test('credit card creation encrypts card number and cvv', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard');

    $raw = \DB::table('credit_cards')->first();
    expect($raw->encrypted_card_number)->not->toBe('4242424242424242');
    expect($raw->encrypted_cvv)->not->toBe('123');
});

test('credit card show page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertSee('My Visa');
    $response->assertSee('John Doe');
});

test('credit card edit page can be rendered', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.edit', [$team, $creditCard]));

    $response->assertOk();
});

test('credit card can be updated', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->set('name', 'My Mastercard')
        ->set('nameOnCard', 'Jane Doe')
        ->set('cardNumber', '5555555555554444')
        ->set('expiryDate', '06/27')
        ->set('cvv', '456')
        ->call('updateCreditCard')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('credit_cards', [
        'id' => $creditCard->id,
        'name' => 'My Mastercard',
        'name_on_card' => 'Jane Doe',
        'last_four' => '4444',
        'expiry_date' => '06/27',
    ]);
});

test('credit card can be deleted', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->call('deleteCreditCard')
        ->assertHasNoErrors()
        ->assertRedirect(route('credit-cards.index', $team));

    $this->assertDatabaseMissing('credit_cards', [
        'id' => $creditCard->id,
    ]);
});

test('credit card cannot be accessed by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($nonMember)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertForbidden();
});

test('guests cannot access credit cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = $this->get(route('credit-cards.index', $team));

    $response->assertRedirect(route('login'));
});

test('credit card creation requires name', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', '')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['name' => 'required']);
});

test('credit card creation requires name on card', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', '')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['nameOnCard' => 'required']);
});

test('credit card creation requires card number', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['cardNumber' => 'required']);
});

test('credit card creation validates card number length', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['cardNumber' => 'min']);
});

test('credit card creation requires cvv', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '')
        ->call('createCreditCard')
        ->assertHasErrors(['cvv' => 'required']);
});

test('credit card masked number accessor works', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    expect($creditCard->masked_number)->toBe('•••• •••• •••• 4242');
});

test('credit card creation redirects to show page', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasNoErrors()
        ->assertRedirect(route('credit-cards.show', [$team, CreditCard::first()]));
});

test('credit card update redirects to show page', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->set('name', 'Updated Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('updateCreditCard')
        ->assertHasNoErrors()
        ->assertRedirect(route('credit-cards.show', [$team, $creditCard]));
});

test('credit card cannot be updated by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $this->actingAs($nonMember);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->set('name', 'Hacked')
        ->set('nameOnCard', 'Hacked')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('updateCreditCard')
        ->assertForbidden();
});

test('credit card cannot be deleted by non team members', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $this->actingAs($nonMember);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->call('deleteCreditCard')
        ->assertForbidden();

    $this->assertDatabaseHas('credit_cards', ['id' => $creditCard->id]);
});

test('credit card notes are encrypted', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->set('notes', 'Business card')
        ->call('createCreditCard');

    $raw = \DB::table('credit_cards')->first();
    expect($raw->encrypted_notes)->not->toBe('Business card');

    $creditCard = CreditCard::first();
    expect($creditCard->notes)->toBe('Business card');
});

test('credit cards index can be searched', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $team->creditCards()->create([
        'name' => 'My Amex',
        'name_on_card' => 'John Doe',
        'card_number' => '378282246310005',
        'expiry_date' => '06/27',
        'cvv' => '1234',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.index', ['team' => $team])
        ->set('search', 'Amex')
        ->assertSee('My Amex')
        ->assertDontSee('My Visa');
});

test('credit card show page displays expiry date', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertSee('12/28');
});

test('credit card show page does not display delete button', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertDontSee('Delete credit card');
});

test('credit card last four strips non-digit characters from card number', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'Formatted',
        'name_on_card' => 'John Doe',
        'card_number' => '4242-4242-4242-4242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    expect($creditCard->last_four)->toBe('4242');
    expect($creditCard->card_number)->toBe('4242-4242-4242-4242');
});

test('credit card update re-encrypts card number and cvv', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $oldEncryptedCardNumber = \DB::table('credit_cards')->first()->encrypted_card_number;
    $oldEncryptedCvv = \DB::table('credit_cards')->first()->encrypted_cvv;

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->set('name', 'My Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4111111111111111')
        ->set('expiryDate', '12/28')
        ->set('cvv', '999')
        ->call('updateCreditCard');

    $raw = \DB::table('credit_cards')->first();
    expect($raw->encrypted_card_number)->not->toBe('4111111111111111');
    expect($raw->encrypted_card_number)->not->toBe($oldEncryptedCardNumber);
    expect($raw->encrypted_cvv)->not->toBe('999');
    expect($raw->encrypted_cvv)->not->toBe($oldEncryptedCvv);
});

test('credit card edit page pre-fills with decrypted card number and cvv', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->assertSet('cardNumber', '4242424242424242')
        ->assertSet('cvv', '123');
});

test('credit card creation requires expiry date', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['expiryDate' => 'required']);
});

test('credit card creation validates cvv max length', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '12345')
        ->call('createCreditCard')
        ->assertHasErrors(['cvv' => 'max']);
});

test('credit card creation validates card number max length', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '42424242424242424242')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['cardNumber' => 'max']);
});

test('credit card notes can be set to null', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
        'notes' => 'Some notes',
    ]);

    expect($creditCard->notes)->toBe('Some notes');

    $creditCard->update(['notes' => null]);

    $raw = \DB::table('credit_cards')->first();
    expect($raw->encrypted_notes)->toBeNull();

    expect($creditCard->fresh()->notes)->toBeNull();
});

test('credit card update updates last four when card number changes', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    expect($creditCard->last_four)->toBe('4242');

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->set('name', 'My Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '5555555555554444')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('updateCreditCard')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('credit_cards', [
        'id' => $creditCard->id,
        'last_four' => '4444',
    ]);
});

test('credit cards search is scoped to the current team', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($otherOwner, ['role' => TeamRole::Owner->value]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $otherTeam->creditCards()->create([
        'name' => 'Leaked Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4111111111111111',
        'expiry_date' => '06/27',
        'cvv' => '456',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.index', ['team' => $team])
        ->set('search', 'John Doe')
        ->assertSee('My Visa')
        ->assertDontSee('Leaked Card');
});

test('credit cards index only shows credit cards for the current team', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($otherOwner, ['role' => TeamRole::Owner->value]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $otherTeam->creditCards()->create([
        'name' => 'Other Card',
        'name_on_card' => 'Other User',
        'card_number' => '4111111111111111',
        'expiry_date' => '06/27',
        'cvv' => '456',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.index', ['team' => $team])
        ->assertSee('My Visa')
        ->assertDontSee('Other Card');
});

test('credit cards are deleted when team is force deleted', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['is_personal' => false]);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    expect(CreditCard::count())->toBe(1);

    $team->forceDelete();

    expect(CreditCard::count())->toBe(0);
});

test('credit card creation rejects invalid Luhn card number', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424241')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['cardNumber']);
});

test('credit card creation rejects expired expiry date', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '01/20')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['expiryDate']);
});

test('credit card creation rejects invalid expiry date format', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.create', ['team' => $team])
        ->set('name', 'My Card')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '13/28')
        ->set('cvv', '123')
        ->call('createCreditCard')
        ->assertHasErrors(['expiryDate']);
});

test('credit card show page displays timestamps', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertSee('Created');
    $response->assertSee('Updated');
});

test('credit card show page displays encryption status', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertSee('Encrypted at rest');
});

test('credit card is_expired accessor returns true for past dates', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'Expired Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '01/20',
        'cvv' => '123',
    ]);

    expect($creditCard->is_expired)->toBeTrue();
});

test('credit card is_expired accessor returns false for future dates', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'Valid Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    expect($creditCard->is_expired)->toBeFalse();
});

test('credit card show page displays team name', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertSee($team->name);
});

test('credit card show page displays expired text for expired cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'Expired Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '01/20',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertSee('Expired');
});

test('credit card index displays expired text for expired cards', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'Expired Card',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '01/20',
        'cvv' => '123',
    ]);

    Livewire::test('pages::credit-cards.index', ['team' => $team])
        ->set('search', 'Expired')
        ->assertSee('Expired');
});

test('credit card edit rejects invalid Luhn card number', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->set('name', 'My Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424241')
        ->set('expiryDate', '12/28')
        ->set('cvv', '123')
        ->call('updateCreditCard')
        ->assertHasErrors(['cardNumber']);
});

test('credit card edit rejects expired expiry date', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.edit', ['team' => $team, 'creditCard' => $creditCard])
        ->set('name', 'My Visa')
        ->set('nameOnCard', 'John Doe')
        ->set('cardNumber', '4242424242424242')
        ->set('expiryDate', '01/20')
        ->set('cvv', '123')
        ->call('updateCreditCard')
        ->assertHasErrors(['expiryDate']);
});

test('credit cards index can be searched by name on card', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $team->creditCards()->create([
        'name' => 'Card A',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $team->creditCards()->create([
        'name' => 'Card B',
        'name_on_card' => 'Jane Smith',
        'card_number' => '4111111111111111',
        'expiry_date' => '06/27',
        'cvv' => '456',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::credit-cards.index', ['team' => $team])
        ->set('search', 'Jane Smith')
        ->assertSee('Card B')
        ->assertDontSee('Card A');
});

test('credit card edit page displays delete button', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.edit', [$team, $creditCard]));

    $response->assertSee('Delete credit card');
});

test('credit card show page embeds card number value in alpine data', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertSee('4242424242424242');
});

test('credit card show page embeds CVV value in alpine data', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '4567',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertSee('4567');
});

test('credit card show page embeds name on card value in alpine data', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertSee('John Doe');
});

test('credit card show page safely encodes name on card with special characters', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John"name</script>',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertDontSee('John"name</script>', false);
});

test('credit card show page safely encodes card number with special characters', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242"42</script>42',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertDontSee('4242"42</script>42', false);
});

test('credit card show page safely encodes CVV with special characters', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '1"23</script>',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertDontSee('1"23</script>', false);
});

test('credit card show page has show/hide notes toggle', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
        'notes' => 'Some notes',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertSee('Show notes');
});

test('credit card show page notes are hidden by default', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
        'notes' => 'Secret note content',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $content = $response->getContent();
    expect($content)->toContain('x-data="{ visible: false }"');
    expect($content)->toContain('x-show="visible"');
});

test('credit card show page renders notes as markdown', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
        'notes' => '**important** info',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertOk();
    $response->assertSee('<strong>important</strong>', false);
});

test('credit card show page does not show notes section when notes are empty', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('credit-cards.show', [$team, $creditCard]));

    $response->assertDontSee('Show notes');
});

test('viewing credit card show page sets last_viewed_at', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();
    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    expect($creditCard->last_viewed_at)->toBeNull();

    Livewire::test('pages::credit-cards.show', ['team' => $team, 'creditCard' => $creditCard]);

    expect($creditCard->fresh()->last_viewed_at)->not->toBeNull();
});

test('viewing credit card show page does not update updated_at', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();
    $creditCard = $team->creditCards()->create([
        'name' => 'My Visa',
        'name_on_card' => 'John Doe',
        'card_number' => '4242424242424242',
        'expiry_date' => '12/28',
        'cvv' => '123',
    ]);

    $originalUpdatedAt = $creditCard->updated_at;

    Carbon::setTestNow(now()->addHour());

    Livewire::test('pages::credit-cards.show', ['team' => $team, 'creditCard' => $creditCard]);

    expect($creditCard->fresh()->updated_at)->toEqual($originalUpdatedAt);

    Carbon::setTestNow();
});
