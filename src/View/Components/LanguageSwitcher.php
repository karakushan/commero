<?php

namespace Commero\View\Components;

use Commero\Support\LocalizedRouteResolver;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitcher extends Component
{
    public function __construct(
        public string $orientation = 'vertical',
        public array $languages = [],
    ) {
        $this->languages = array_map(
            fn (array $language): array => [
                ...$language,
                'label' => $language['code'] === 'uk' ? 'UA' : $language['label'],
            ],
            app(LocalizedRouteResolver::class)->languageOptions(),
        );
    }

    public function render(): View|Closure|string
    {
        return view('components.language-switcher');
    }
}
