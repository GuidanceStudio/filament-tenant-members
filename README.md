# Filament Members

Multi-tenant organization, member and invitation management for Laravel Filament.

Provides a complete member/invite system for Filament v5 multi-tenant panels, plus an admin panel for superadmin user/organization management. Works out-of-the-box with zero configuration, or fully customizable with your own models and role enums.

## Requirements

- PHP 8.3+
- Laravel 13+
- Filament 5+

## Installation

```bash
composer require guidance-studio/filament-tenant-members
```

Run the migrations:

```bash
php artisan migrate
```

This creates three tables (`organizations`, `organization_user` pivot, and `organization_invites`) and adds a `role` column to the existing `users` table for system-level (landlord) roles. The column defaults to the value of `default_role` in the config.

## Quick Start

### 1. User Model

Add the `HasOrganizations` trait to your User model:

```php
use Guidance\FilamentTenantMembers\Concerns\HasOrganizations;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasOrganizations;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->role === 'admin'; // your logic here
        }

        return true;
    }
}
```

### 2. Organization Model (optional)

The package provides its own `Organization` model that works out-of-the-box. If you need to extend it, create your own model with the `IsOrganization` trait:

```php
use Guidance\FilamentTenantMembers\Concerns\IsOrganization;

class Organization extends Model
{
    use IsOrganization;

    // your custom methods...
}
```

Then update the config to point to your model (see [Configuration](#configuration)).

### 3. Organization Panel

Register the plugin and the package's pages in your `OrganizationPanelProvider`:

```php
use Guidance\FilamentTenantMembers\FilamentTenantMembersPlugin;
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Auth\Register;
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Tenancy\RegisterOrganization;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...your panel config...
        ->registration(Register::class)
        ->tenant(Organization::class, slugAttribute: 'slug')
        ->tenantRegistration(RegisterOrganization::class)
        ->plugin(FilamentTenantMembersPlugin::make());
}
```

### 4. Admin Panel

Register the admin plugin in your `AdminPanelProvider`:

```php
use Guidance\FilamentTenantMembers\FilamentTenantMembersAdminPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...your panel config...
        ->plugin(FilamentTenantMembersAdminPlugin::make());
}
```

That's it. You now have:

- A **Settings** cluster in the organization panel sidebar with:
  - **General** — edit organization name and slug
  - **Members** — member list and invite management
- An invite acceptance flow via email (handles both existing and new users)
- **User** and **Organization** resources in the admin panel

## Features

### Settings Cluster

The plugin adds a **Settings** cluster to the organization panel sidebar with two sub-pages: General and Members. External packages or the application itself can add their own pages to the cluster by declaring:

```php
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Clusters\Settings;

class BillingSettings extends Page
{
    protected static ?string $cluster = Settings::class;
    // ...
}
```

Register the page via `->pages([BillingSettings::class])` in your panel provider or another plugin and it will appear automatically in the Settings sub-navigation.

### General Settings Page

Accessible at **Settings → General**. Provides a form to edit the organization's name and slug with validation (slug format + uniqueness).

### Members Page

Accessible at **Settings → Members**. Contains two tabs:

- **Members** — lists all organization members with their roles. Members with the `canManageMembers()` permission can change roles, transfer ownership, and remove members. Protected roles (like Owner) cannot be modified or removed. Non-owner members can leave the organization from their own row.
- **Pending Invitations** — lists active invitations with options to resend or cancel. Shows a badge count of pending invites. The resend button is hidden during the cooldown period (default: 5 minutes) to prevent spam.

The **Invite Users** button is only visible to members whose role returns `true` from `canInviteMembers()`. It opens a modal where you can invite multiple users at once, each with a specific role.

### Role-Based Permissions

Access control is driven entirely by the role enum. Each role defines its own permissions via the `RoleEnum` contract:

| Method | Purpose | Default (Owner) | Default (Admin) | Default (User) |
|---|---|---|---|---|
| `canInviteMembers()` | Can see and use the "Invite Users" button | Yes | Yes | No |
| `canManageMembers()` | Can change roles and remove members | Yes | Yes | No |
| `isProtected()` | Cannot be modified or removed by others | Yes | No | No |
| `ownerValue()` | Role value assigned to the organization creator | — | — | — |

When you create a custom role enum, you define these permissions per role. No config needed.

### Leave Organization

Non-owner members see a **Leave** button on their own row in the members table. Clicking it detaches them from the organization immediately and redirects to the Filament home URL. Members with a protected role (e.g., Owner) cannot leave — they must transfer ownership first.

### Transfer Ownership

The organization owner can transfer ownership to another member via the action menu. This:

1. Assigns the `ownerValue()` role to the target member.
2. Demotes the current owner to the `default_role` (e.g., User).

The transfer ownership action is only visible to members whose role returns `true` from `isProtected()`.

### Resend Cooldown

After an invitation is resent, the **Resend** button is hidden for a configurable cooldown period (default: 5 minutes) to prevent email spam. Configure via `resend_cooldown_minutes` in the config file.

### Invite Flow

1. A member with invite permissions sends an invitation from the Members page.
2. The invitee receives an email with an accept link (`/invite/{token}`).
3. Clicking the link routes through the `AcceptInviteController`:
   - **Logged-in user with matching email**: redirected to the `AcceptInvite` page, which shows the organization name and inviter. The user can accept or decline.
   - **Logged-in user with a different email**: redirected to the `AcceptInvite` page, which shows an email mismatch error with a logout button.
   - **Existing user, not logged in**: the token is stored in the session and the user is redirected to login. After login, the `AcceptInvite` page is opened automatically via the `url.intended` mechanism.
   - **New user**: the token and email are stored in the session and the user is redirected to registration. The email field is pre-filled and locked. After registration, the `AcceptInvite` page is opened automatically.
4. On the `AcceptInvite` page, the user clicks **Accept invitation** or **Decline**. Accepting adds the user to the organization with the invited role and redirects to the organization panel.

Invitations expire after a configurable number of days (default: 7). Expired invitations can be resent, which resets the expiration.

### Registration Page

The package provides a custom registration page (`Register`) that:

- Pre-fills the email field when a user arrives via an invite link.
- Disables the email field so the user cannot change it.
- Otherwise behaves identically to the default Filament registration page.

### Tenancy Pages

- **RegisterOrganization** — form to create a new organization (name + slug). The slug is auto-generated from the name. The creator is assigned the role returned by `ownerValue()`.

### Admin Panel

- **UserResource** — list, filter, and edit all users. Displays system role (landlord role), email verification status, and creation date.
- **OrganizationResource** — list and edit all organizations. Displays member count and creation date.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=filament-tenant-members-config
```

This creates `config/filament-tenant-members.php`:

```php
return [
    // Model classes
    'models' => [
        'user' => \App\Models\User::class,
        'organization' => \Guidance\FilamentTenantMembers\Models\Organization::class,
    ],

    // Role enum for organization roles (Owner, Admin, User)
    // Must implement Guidance\FilamentTenantMembers\Contracts\RoleEnum
    'role_enum' => \Guidance\FilamentTenantMembers\Enums\DefaultRole::class,

    // Role enum for system-level roles (Admin, User)
    // Used in the admin panel's UserResource
    // Must implement Filament\Support\Contracts\HasLabel
    'landlord_role_enum' => \Guidance\FilamentTenantMembers\Enums\DefaultLandlordRole::class,

    // Default role assigned to new members via invite
    'default_role' => 'user',

    // Days until an invitation expires
    'invite_expires_days' => 7,

    // Maximum number of invitations per batch
    'max_invites_per_batch' => 5,

    // Minutes before an invitation can be resent
    'resend_cooldown_minutes' => 5,

    // Attribute used for tenant URL slugs
    'tenant_slug_attribute' => 'slug',

    // Filament panel ID for the organization panel
    'panel_id' => 'organization',

    // Route configuration for the invite acceptance endpoint
    'routes' => [
        'prefix' => 'invite',              // URL: /invite/{token}
        'middleware' => ['web', 'throttle:10,1'],
    ],
];
```

### Custom Role Enum

The package ships with a `DefaultRole` enum (Owner, Admin, User) that works without any configuration. To use your own roles, create an enum that implements the `RoleEnum` contract:

```php
use Guidance\FilamentTenantMembers\Contracts\RoleEnum;

enum OrganizationRole: string implements RoleEnum
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Owner => 'warning',
            self::Admin => 'info',
            self::Editor => 'success',
            self::Viewer => 'gray',
        };
    }

    public function canInviteMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            default => false,
        };
    }

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            default => false,
        };
    }

    public function isProtected(): bool
    {
        return $this === self::Owner;
    }

    public static function ownerValue(): string
    {
        return self::Owner->value;
    }

    public static function assignableOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $role) => $role->isProtected())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->getLabel()])
            ->all();
    }

    public static function orderBySql(string $column): string
    {
        $cases = collect(self::cases())
            ->map(fn (self $role, int $index) => "WHEN '{$role->value}' THEN {$index}")
            ->implode(' ');

        return "CASE {$column} {$cases} ELSE " . count(self::cases()) . ' END';
    }
}
```

Then set it in your config:

```php
'role_enum' => \App\Enums\OrganizationRole::class,
```

### RoleEnum Contract Reference

The `RoleEnum` interface extends Filament's `HasLabel` and `HasColor` and requires these methods:

| Method | Type | Description |
|---|---|---|
| `getLabel()` | instance | Display name for the role (badge text) |
| `getColor()` | instance | Badge color (`warning`, `info`, `gray`, etc.) |
| `canInviteMembers()` | instance | Whether this role can invite new members |
| `canManageMembers()` | instance | Whether this role can change roles and remove members |
| `isProtected()` | instance | Whether this role cannot be modified or removed |
| `ownerValue()` | static | The role value assigned to organization creators |
| `assignableOptions()` | static | Roles available in the invite form and role change modal |
| `orderBySql()` | static | SQL CASE expression for ordering members by role priority |

### Custom Landlord Role Enum

The `landlord_role_enum` is used by the admin panel's `UserResource` to display and filter users by their system-level role. The default provides Admin and User. To customize:

```php
'landlord_role_enum' => \App\Enums\LandlordRole::class,
```

Your enum must implement `Filament\Support\Contracts\HasLabel`.

### Custom Organization Model

If you need additional fields or methods on the Organization model:

```php
use Guidance\FilamentTenantMembers\Concerns\IsOrganization;

class Organization extends Model
{
    use IsOrganization;

    // Add your custom fields, relationships, etc.
}
```

Update the config:

```php
'models' => [
    'organization' => \App\Models\Organization::class,
],
```

## Publishing

The package works out-of-the-box without publishing anything. Migrations are loaded automatically, views are served from the package, and the default config covers common use cases. Publishing is only needed when you want to customize something.

Publish everything at once:

```bash
php artisan vendor:publish --provider="Guidance\FilamentTenantMembers\FilamentTenantMembersServiceProvider"
```

Or publish individually by tag:

### Config

```bash
php artisan vendor:publish --tag=filament-tenant-members-config
```

Publishes `config/filament-tenant-members.php`. Required if you want to use custom role enums, custom models, or change any default settings. See [Configuration](#configuration) for all available options.

### Views

```bash
php artisan vendor:publish --tag=filament-tenant-members-views
```

Publishes the invitation email Blade template to `resources/views/vendor/filament-tenant-members/mail/organization-invite.blade.php`. Useful if you want to customize the email layout, wording, or branding.

### Migrations

```bash
php artisan vendor:publish --tag=filament-tenant-members-migrations
```

Publishes the migration files to `database/migrations/`. The package creates three tables:

- `organizations` — stores organization name and slug
- `organization_user` — pivot table with role column
- `organization_invites` — invitation tokens, roles, and expiration

A fourth migration adds a `role` column to the existing `users` table to store the system-level (landlord) role.

Migrations are loaded automatically from the package, so publishing is only needed if you want to modify the table structure (e.g., add extra columns to `organizations`). If you publish, the package will skip its own copies to avoid duplicates.

## Events

The package dispatches the following event:

- `Guidance\FilamentTenantMembers\Events\OrganizationInviteCreated` — fired when an invitation is created or resent. Contains the `OrganizationInvite` model instance.

The package registers two listeners internally:

- `SendOrganizationInviteMail` — sends the invitation email (queued).
- `AcceptPendingInvite` — listens to `Illuminate\Auth\Events\Login`. If a pending invite token is stored in the session (set before login/registration), it sets `url.intended` to the `AcceptInvite` page so the user is redirected there after authenticating.

To add custom logic when an invite is created, register your own listener for `OrganizationInviteCreated`:

```php
use Guidance\FilamentTenantMembers\Events\OrganizationInviteCreated;

Event::listen(OrganizationInviteCreated::class, function (OrganizationInviteCreated $event) {
    // $event->invite — the OrganizationInvite model
});
```

## Console Commands

### Cleanup Expired Invites

```bash
php artisan filament-tenant-members:cleanup-invites
```

Deletes all expired, unaccepted invitations (rows where `expires_at` is in the past and `accepted_at` is `null`). Schedule it from your application's `routes/console.php` (or `app/Console/Kernel.php`) if you want it to run automatically — the package does not register a schedule itself.

## Package Structure

```
src/
├── Concerns/
│   ├── HasOrganizations.php         # Trait for User model (relationships, tenancy)
│   └── IsOrganization.php           # Trait for Organization model (relationships)
├── Contracts/
│   └── RoleEnum.php                 # Interface for custom role enums
├── Enums/
│   ├── DefaultRole.php              # Built-in organization roles
│   └── DefaultLandlordRole.php      # Built-in system roles
├── Events/
│   └── OrganizationInviteCreated.php
├── Console/Commands/
│   └── CleanupExpiredInvites.php    # Deletes expired, unaccepted invitations
├── Filament/
│   ├── OrganizationPanel/
│   │   ├── Clusters/
│   │   │   └── Settings.php         # Settings cluster (General + Members sub-nav)
│   │   └── Pages/
│   │       ├── AcceptInvite.php     # Accept/decline invitation page (simple layout)
│   │       ├── Members.php          # Members & invitations tabs (inside Settings cluster)
│   │       ├── Auth/Register.php
│   │       ├── Settings/
│   │       │   └── GeneralSettings.php  # Edit organization name & slug
│   │       └── Tenancy/
│   │           └── RegisterOrganization.php
│   └── AdminPanel/Resources/        # UserResource, OrganizationResource
├── Http/Controllers/
│   └── AcceptInviteController.php   # Routes invite link → login/register/AcceptInvite page
├── Listeners/
│   ├── SendOrganizationInviteMail.php
│   └── AcceptPendingInvite.php
├── Livewire/
│   ├── ListMembers.php              # Members table component
│   └── ListInvitations.php          # Invitations table component
├── Mail/
│   └── OrganizationInviteMail.php   # Queued mailable
├── Models/
│   ├── Organization.php             # Default organization model
│   └── OrganizationInvite.php       # Invitation model
├── FilamentTenantMembers.php              # Config helper
├── FilamentTenantMembersPlugin.php        # Organization panel plugin
├── FilamentTenantMembersAdminPlugin.php   # Admin panel plugin
└── FilamentTenantMembersServiceProvider.php
```

## License

MIT
