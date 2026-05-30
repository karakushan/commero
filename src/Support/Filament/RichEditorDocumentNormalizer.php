<?php

namespace Commero\Support\Filament;

class RichEditorDocumentNormalizer
{
    /**
     * @param  mixed  $state
     * @return mixed
     */
    public static function ensureTrailingParagraph(mixed $state): mixed
    {
        if (! is_array($state)) {
            return $state;
        }

        if (($state['type'] ?? null) !== 'doc') {
            return $state;
        }

        $content = $state['content'] ?? null;

        if (! is_array($content) || $content === []) {
            $state['content'] = [
                [
                    'type' => 'paragraph',
                    'content' => [],
                ],
            ];

            return $state;
        }

        $lastNode = end($content);

        if (! is_array($lastNode)) {
            return $state;
        }

        if (($lastNode['type'] ?? null) !== 'customBlock') {
            return $state;
        }

        $content[] = [
            'type' => 'paragraph',
            'content' => [],
        ];

        $state['content'] = $content;

        return $state;
    }
}
