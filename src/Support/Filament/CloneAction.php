<?php

declare(strict_types=1);

namespace Commero\Support\Filament;

use Commero\Models\MarketingLead;
use Commero\Models\Order;
use Commero\Models\OrderStatus;
use Commero\Models\Page;
use Commero\Models\Post;
use Commero\Models\Product;
use Commero\Models\ProductReview;
use Commero\Services\RecordCloneService;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CloneAction extends ReplicateAction
{
    public static function getDefaultName(): ?string
    {
        return 'clone';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('commero::admin.actions.clone.label'))
            ->modalHeading(fn (): string => __('commero::admin.actions.clone.modal.heading', ['label' => $this->getRecordTitle()]))
            ->modalSubmitActionLabel(__('commero::admin.actions.clone.modal.submit'))
            ->successNotificationTitle(__('commero::admin.actions.clone.notifications.success'))
            ->schema(fn (Model $record): array => array_key_exists('status', $record->getAttributes()) ? [
                Select::make('status')
                    ->label(__('commero::admin.actions.clone.status'))
                    ->options(fn (): array => $this->statusOptions($record))
                    ->default($record->getRawOriginal('status'))
                    ->required(),
            ] : [])
            ->using(function (array $data, Model $record): Model {
                $overrides = array_key_exists('status', $data)
                    ? ['status' => $data['status']]
                    : [];
                $this->replica = app(RecordCloneService::class)->replicate($record, $overrides);

                return $this->replica;
            });
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(Model $record): array
    {
        return match (true) {
            $record instanceof Product => [
                'draft' => __('commero::admin.product.status.draft'),
                'published' => __('commero::admin.product.status.published'),
            ],
            $record instanceof Post, $record instanceof Page => [
                'draft' => __('commero::admin.content.status.draft'),
                'published' => __('commero::admin.content.status.published'),
            ],
            $record instanceof ProductReview => [
                'pending' => __('commero::admin.product_review.status.pending'),
                'approved' => __('commero::admin.product_review.status.approved'),
                'rejected' => __('commero::admin.product_review.status.rejected'),
            ],
            $record instanceof MarketingLead => [
                'new' => __('commero::admin.marketing_lead.statuses.new'),
                'processed' => __('commero::admin.marketing_lead.statuses.processed'),
            ],
            $record instanceof Order => OrderStatus::query()
                ->withTranslationsFor(app()->getLocale())
                ->where(fn (Builder $query): Builder => $query
                    ->where('is_active', true)
                    ->orWhere('code', $record->getRawOriginal('status')))
                ->orderBy('sort')
                ->get()
                ->mapWithKeys(fn (OrderStatus $status): array => [$status->code => $status->name])
                ->all(),
            default => $record->newQuery()
                ->whereNotNull('status')
                ->distinct()
                ->pluck('status')
                ->filter()
                ->mapWithKeys(fn (mixed $status): array => [(string) $status => (string) $status])
                ->all(),
        };
    }
}
