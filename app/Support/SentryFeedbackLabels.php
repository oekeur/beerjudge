<?php

namespace App\Support;

class SentryFeedbackLabels
{
    /**
     * Option name => lang key suffix for every text option of Sentry's feedbackIntegration.
     */
    private const OPTIONS = [
        'triggerLabel',
        'triggerAriaLabel',
        'cancelButtonLabel',
        'submitButtonLabel',
        'confirmButtonLabel',
        'formTitle',
        'emailLabel',
        'emailPlaceholder',
        'messageLabel',
        'messagePlaceholder',
        'nameLabel',
        'namePlaceholder',
        'successMessageText',
        'isRequiredLabel',
        'addScreenshotButtonLabel',
        'removeScreenshotButtonLabel',
        'highlightToolText',
        'hideToolText',
        'removeHighlightText',
        'errorEmptyMessageText',
        'errorNoClientText',
        'errorTimeoutText',
        'errorForbiddenText',
        'errorGenericText',
    ];

    /**
     * Translated feedback widget texts in the current locale, keyed by Sentry option name.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $labels = [];
        foreach (self::OPTIONS as $option) {
            $labels[$option] = __('feedback.'.$option);
        }

        return $labels;
    }

    public static function json(): string
    {
        return self::attributeJson(self::all());
    }

    /**
     * Encodes a value for a single-quoted HTML attribute: apostrophes, tags and ampersands become
     * \u escapes, so a name like O'Brien cannot end the attribute early.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function attributeJson(?array $data): string
    {
        return json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
