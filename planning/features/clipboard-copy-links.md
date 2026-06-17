# Plan: Clipboard Copy for Generated Links

## Context

Three places in the admin panel generate or display shareable URLs with no clipboard support:

1. **Invite link** (Members list) — header action generates a token and shows a notification with the URL as the body; no copy affordance.
2. **Entry submission link** (Competition form → Entries tab) — plain URL text in a `Placeholder` component.
3. **Judge participation link** (Competition form → Judging tab) — plain URL text in a `Placeholder` component.

The invite link has two types with different UX needs:
- `single_use` — one-shot token, consumed after first registration. Show URL + copy button in the notification when generated.
- `time_bound` — reusable until expiry. Show all currently valid ones persistently on the Members page with copy buttons so admins can re-share without re-generating.

`Invitation::scopeValid()` already filters to `used_at IS NULL AND (expires_at IS NULL OR expires_at > now())`.

---

## Files to Modify

| File | Change |
|------|--------|
| `app/Filament/Resources/Members/Pages/ListMembers.php` | Register `InviteLinksWidget` in `getHeaderWidgets()`; add `NotificationAction` copy button to the invite notification |
| `app/Filament/Resources/Members/Widgets/InviteLinksWidget.php` | **New** — queries valid time-bound invites, passes to Blade view |
| `resources/views/filament/members/invite-links-widget.blade.php` | **New** — renders each invite URL with Alpine copy button |
| `app/Filament/Resources/Competitions/Schemas/CompetitionForm.php` | Add `copyableLinkHtml()` helper; use it in `entry_link` and `judge_link` placeholders |
| `lang/en.json` | New keys: `invite_links_active`, `invite_expires`, `invite_copy`, `invite_copied` |
| `lang/nl.json` | Dutch translations for the same keys |

---

## 1. `InviteLinksWidget.php` (new)

```php
namespace App\Filament\Resources\Members\Widgets;

use App\Models\Invitation;
use Filament\Widgets\Widget;

class InviteLinksWidget extends Widget
{
    protected static string $view = 'filament.members.invite-links-widget';
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $invitations = Invitation::valid()
            ->where('type', Invitation::TYPE_TIME_BOUND)
            ->orderBy('expires_at')
            ->get();

        return [
            'invitations' => $invitations->map(fn (Invitation $inv) => [
                'url'        => route('register', ['token' => $inv->token]),
                'expires_at' => $inv->expires_at?->format('d M Y, H:i'),
            ])->all(),
        ];
    }
}
```

---

## 2. `invite-links-widget.blade.php` (new)

Renders nothing when `count($invitations) === 0`. When invites exist: a Filament-styled section with one row per invite — a readonly text input showing the URL (selectable), an expiry label, and a copy button with Alpine `copied` toggle.

Alpine pattern per row:

```html
<div x-data="{ copied: false }">
    <button
        type="button"
        x-on:click="navigator.clipboard.writeText('{{ $inv['url'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
        x-text="copied ? '{{ __('competition.invite_copied') }}' : '{{ __('competition.invite_copy') }}'"
    ></button>
</div>
```

---

## 3. `ListMembers.php` changes

Add `getHeaderWidgets()`:

```php
protected function getHeaderWidgets(): array
{
    return [InviteLinksWidget::class];
}
```

Add a `NotificationAction` copy button to the notification in `->action()`:

```php
use Filament\Notifications\Actions\Action as NotificationAction;

Notification::make()
    ->title(__('competition.invite_created'))
    ->body($url)
    ->persistent()
    ->success()
    ->actions([
        NotificationAction::make('copy')
            ->label(__('competition.invite_copy'))
            ->extraAttributes([
                'x-data' => '{ copied: false }',
                'x-on:click' => "navigator.clipboard.writeText('" . addslashes($url) . "'); copied = true; setTimeout(() => copied = false, 2000)",
                'x-text' => "copied ? '" . __('competition.invite_copied') . "' : '" . __('competition.invite_copy') . "'",
            ]),
    ])
    ->send();
```

---

## 4. `CompetitionForm.php` changes

Add a private static helper:

```php
private static function copyableLinkHtml(string $url): string
{
    $escaped = e($url);
    $json    = json_encode($url);

    return <<<HTML
    <div class="flex items-center gap-2 text-sm">
        <span class="font-mono break-all text-gray-700 dark:text-gray-300">{$escaped}</span>
        <button
            x-data="{ copied: false }"
            x-on:click="navigator.clipboard.writeText({$json}); copied = true; setTimeout(() => copied = false, 2000)"
            type="button"
            class="shrink-0 inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 transition-colors"
        >
            <svg x-show="!copied" ...clipboard icon... />
            <svg x-show="copied" ...check icon... />
            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
        </button>
    </div>
    HTML;
}
```

Use in both placeholders:

```php
Placeholder::make('entry_link')
    ->content(fn (?Competition $record) => $record?->exists
        ? new HtmlString(self::copyableLinkHtml(route('competitions.entries.create', $record)))
        : __('competition.save_first')
    )

Placeholder::make('judge_link')
    ->content(fn (?Competition $record) => $record?->exists
        ? new HtmlString(self::copyableLinkHtml(route('judging.show', ['competition' => $record->id, 'register' => 1])))
        : __('competition.save_first')
    )
```

---

## 5. Translation Keys

| Key | English | Dutch |
|-----|---------|-------|
| `competition.invite_links_active` | Active multi-use invite links | Actieve meervoudige uitnodigingslinks |
| `competition.invite_expires` | Expires | Verloopt |
| `competition.invite_copy` | Copy | Kopiëren |
| `competition.invite_copied` | Copied! | Gekopieerd! |

---

## Verification Checklist

**Time-bound invite widget:**
- [ ] Members list → generate a time-bound invite → widget appears above the table showing the URL with copy button and expiry date
- [ ] Click Copy → paste elsewhere to verify the URL is correct
- [ ] When no valid time-bound invites exist, the widget renders nothing

**Single-use invite notification:**
- [ ] Generate a single-use invite → notification appears with URL body + Copy button
- [ ] Click Copy → paste elsewhere to verify the URL is correct

**Competition form — entry link:**
- [ ] Edit a competition → Entries tab → URL renders as monospace text with copy button
- [ ] Click Copy → icon flips to check briefly, then reverts
- [ ] Paste to verify the URL is correct

**Competition form — judge link:**
- [ ] Edit a competition → Judging tab → same behaviour as entry link

**General:**
- [ ] `./vendor/bin/phpstan analyse` passes
- [ ] `php artisan test --compact` passes
  
