<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Joranski\FilamentComments\Attachments\DefaultCommentAttachmentHandler;
use Joranski\FilamentComments\Attachments\NullCommentAttachmentHandler;
use Joranski\FilamentComments\Contracts\CommentAttachmentHandler;

final class CommentAttachments
{
    public static function handler(): CommentAttachmentHandler
    {
        $class = (string) config('filament-comments.attachments.handler', DefaultCommentAttachmentHandler::class);

        if (! is_subclass_of($class, CommentAttachmentHandler::class)) {
            return app(NullCommentAttachmentHandler::class);
        }

        return app($class);
    }

    public static function enabled(?CommentAttachmentContext $context = null): bool
    {
        if (! (bool) config('filament-comments.features.attachments', false)) {
            return false;
        }

        if (! (bool) config('filament-comments.attachments.enabled', true)) {
            return false;
        }

        if ($context === null) {
            return true;
        }

        return self::handler()->isEnabled(context: $context);
    }

    /**
     * @return list<string>
     */
    public static function acceptedFileTypes(): array
    {
        $configured = config('filament-comments.attachments.accepted_file_types');

        if (! is_array($configured)) {
            return CommentAttachmentDefaults::acceptedFileTypes();
        }

        return array_values(array_map(strval(...), $configured));
    }

    public static function maxSizeKb(): ?int
    {
        $maxSize = config('filament-comments.attachments.max_size_kb');

        return is_numeric($maxSize) ? (int) $maxSize : null;
    }

    /**
     * Validation rules for one uploaded composer attachment.
     *
     * @return list<string>
     */
    public static function fileRules(): array
    {
        $rules = ['file'];

        $types = self::acceptedFileTypes();

        if ($types !== []) {
            $rules[] = 'mimetypes:'.implode(',', $types);
        }

        $maxSize = self::maxSizeKb();

        if ($maxSize !== null) {
            $rules[] = 'max:'.$maxSize;
        }

        return $rules;
    }

    /**
     * Store an uploaded file through the configured handler and return the
     * embeddable body markup for it.
     */
    public static function storeAsHtml(UploadedFile $file, CommentAttachmentContext $context): string
    {
        $handler = self::handler();
        $reference = $handler->store(file: $file, context: $context);

        return CommentBodyAttachments::html(
            url: $handler->url(reference: $reference, context: $context) ?? $reference,
            name: $file->getClientOriginalName(),
        );
    }

    public static function afterCommentSaved(Model $comment, CommentAttachmentContext $context): void
    {
        if (! self::enabled(context: $context)) {
            return;
        }

        self::handler()->afterCommentSaved(comment: $comment, context: $context);
    }

    public static function beforeCommentDeleted(Model $comment): void
    {
        if (! (bool) config('filament-comments.features.attachments', false)) {
            return;
        }

        self::handler()->beforeCommentDeleted(comment: $comment);
    }
}
