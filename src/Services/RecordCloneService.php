<?php

declare(strict_types=1);

namespace Commero\Services;

use Closure;
use Commero\Models\AttributeGroup;
use Commero\Models\AttributeOption;
use Commero\Models\Category;
use Commero\Models\CityCategory;
use Commero\Models\Currency;
use Commero\Models\Order;
use Commero\Models\OrderStatus;
use Commero\Models\Page;
use Commero\Models\PaymentMethod;
use Commero\Models\Post;
use Commero\Models\PostCategory;
use Commero\Models\Product;
use Commero\Models\ProductAttribute;
use Commero\Models\ProductReview;
use Commero\Models\ShippingMethod;
use Commero\Models\User;
use Commero\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

final class RecordCloneService
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public function replicate(Model $record, array $overrides = []): Model
    {
        return DB::transaction(fn (): Model => $this->replicateRecord($record, $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function replicateRecord(Model $record, array $overrides): Model
    {
        $clone = match (true) {
            $record instanceof Product => $this->replicateProduct($record),
            $record instanceof Category => $this->replicateCategory($record),
            $record instanceof CityCategory => $this->replicateCityCategory($record),
            $record instanceof Post => $this->replicatePost($record),
            $record instanceof PostCategory => $this->replicatePostCategory($record),
            $record instanceof Page => $this->replicatePage($record),
            $record instanceof AttributeGroup => $this->replicateAttributeGroup($record),
            $record instanceof ProductAttribute => $this->replicateProductAttribute($record),
            $record instanceof AttributeOption => $this->replicateAttributeOption($record),
            $record instanceof PaymentMethod => $this->replicatePaymentMethod($record),
            $record instanceof ShippingMethod => $this->replicateShippingMethod($record),
            $record instanceof OrderStatus => $this->replicateOrderStatus($record),
            $record instanceof Order => $this->replicateOrder($record),
            $record instanceof ProductReview => $this->replicateProductReview($record),
            $record instanceof User => $this->replicateUser($record),
            default => $this->replicateGeneric($record),
        };

        if (array_key_exists('status', $overrides) && array_key_exists('status', $clone->getAttributes())) {
            $clone->forceFill(['status' => $overrides['status']])->saveQuietly();
        }

        return $clone->refresh();
    }

    private function replicateProduct(Product $record): Product
    {
        /** @var Product $clone */
        $clone = $this->replicateModel($record, [
            'uuid' => (string) Str::uuid(),
            'sku' => $this->uniqueModelValue($record, 'sku'),
        ]);

        $this->cloneTranslations($record, $clone);

        $variantIds = [];

        foreach ($record->variants()->get() as $variant) {
            $variantClone = $variant->replicate(['sku']);
            $variantClone->forceFill([
                'product_id' => $clone->getKey(),
                'sku' => $this->uniqueModelValue($variant, 'sku'),
            ]);
            $variantClone->saveQuietly();
            $variantIds[$variant->getKey()] = $variantClone->getKey();
        }

        foreach ($record->images()->get() as $image) {
            $imageClone = $image->replicate();
            $imageClone->forceFill(['product_id' => $clone->getKey()]);
            $imageClone->saveQuietly();
            $this->copyMedia($image, $imageClone);
        }

        foreach ($record->attributeValues()->get() as $attributeValue) {
            $attributeValueClone = $attributeValue->replicate();
            $attributeValueClone->forceFill([
                'product_id' => $clone->getKey(),
                'variant_id' => $attributeValue->variant_id !== null
                    ? ($variantIds[$attributeValue->variant_id] ?? null)
                    : null,
            ]);
            $attributeValueClone->saveQuietly();
        }

        foreach ($record->faqs()->get() as $faq) {
            $faqClone = $faq->replicate();
            $faqClone->forceFill(['product_id' => $clone->getKey()]);
            $faqClone->saveQuietly();
        }

        $clone->categories()->sync($record->categories()->pluck('categories.id')->all());
        $this->cloneProductRelations($record, $clone);

        return $clone->refresh();
    }

    private function replicateCategory(Category $record): Category
    {
        /** @var Category $clone */
        $clone = $this->replicateModel($record);
        $translations = $this->cloneTranslations($record, $clone);
        $this->syncHierarchicalPath($clone, $translations, includeParentPath: true);

        $this->syncBelongsToMany($record, $clone, 'products');
        $this->copyMedia($record, $clone);

        return $clone->refresh();
    }

    private function replicateCityCategory(CityCategory $record): CityCategory
    {
        /** @var CityCategory $clone */
        $clone = $this->replicateModel($record);
        $translations = $this->cloneTranslations($record, $clone);
        $this->syncHierarchicalPath($clone, $translations, includeParentPath: true);

        $pivot = $record->categories()->get()->mapWithKeys(
            fn (Model $category): array => [$category->getKey() => [
                'sort' => (int) ($category->pivot?->sort ?? 0),
            ]],
        )->all();

        $clone->categories()->sync($pivot);

        return $clone->refresh();
    }

    private function replicatePost(Post $record): Post
    {
        /** @var Post $clone */
        $clone = $this->replicateModel($record);
        $this->cloneTranslations($record, $clone);
        $this->copyMedia($record, $clone);

        return $clone->refresh();
    }

    private function replicatePostCategory(PostCategory $record): PostCategory
    {
        /** @var PostCategory $clone */
        $clone = $this->replicateModel($record);
        $translations = $this->cloneTranslations($record, $clone);
        $this->syncHierarchicalPath($clone, $translations, includeParentPath: false);

        return $clone->refresh();
    }

    private function replicatePage(Page $record): Page
    {
        /** @var Page $clone */
        $clone = $this->replicateModel($record);
        $this->cloneTranslations($record, $clone);

        return $clone->refresh();
    }

    private function replicateAttributeGroup(AttributeGroup $record): AttributeGroup
    {
        /** @var AttributeGroup $clone */
        $clone = $this->replicateModel($record, [
            'code' => $this->uniqueModelValue($record, 'code'),
        ]);

        foreach ($record->attributes()->get() as $attribute) {
            $attributeClone = $attribute->replicate(['code']);
            $attributeClone->forceFill([
                'group_id' => $clone->getKey(),
                'code' => $this->uniqueModelValue($attribute, 'code'),
            ]);
            $attributeClone->saveQuietly();
            $this->cloneTranslations($attribute, $attributeClone);
            $this->cloneAttributeOptions($attribute, $attributeClone);
        }

        return $clone->refresh();
    }

    private function replicateProductAttribute(ProductAttribute $record): ProductAttribute
    {
        /** @var ProductAttribute $clone */
        $clone = $this->replicateModel($record, [
            'code' => $this->uniqueModelValue($record, 'code'),
        ]);
        $this->cloneTranslations($record, $clone);
        $this->cloneAttributeOptions($record, $clone);

        return $clone->refresh();
    }

    private function cloneAttributeOptions(ProductAttribute $record, ProductAttribute $clone): void
    {
        foreach ($record->options()->get() as $option) {
            $optionClone = $option->replicate();
            $optionClone->forceFill(['attribute_id' => $clone->getKey()]);
            $optionClone->saveQuietly();
            $this->cloneTranslations($option, $optionClone);
        }
    }

    private function replicateAttributeOption(AttributeOption $record): AttributeOption
    {
        /** @var AttributeOption $clone */
        $clone = $this->replicateModel($record, [
            'value' => $this->uniqueModelValue($record, 'value', function (Builder $query) use ($record): Builder {
                return $query->where('attribute_id', $record->attribute_id);
            }),
        ]);
        $this->cloneTranslations($record, $clone);

        return $clone->refresh();
    }

    private function replicatePaymentMethod(PaymentMethod $record): PaymentMethod
    {
        /** @var PaymentMethod $clone */
        $clone = $this->replicateModel($record, [
            'code' => $this->uniqueModelValue($record, 'code'),
        ]);
        $this->cloneTranslations($record, $clone);

        return $clone->refresh();
    }

    private function replicateShippingMethod(ShippingMethod $record): ShippingMethod
    {
        /** @var ShippingMethod $clone */
        $clone = $this->replicateModel($record, [
            'code' => $this->uniqueModelValue($record, 'code'),
        ]);
        $this->cloneTranslations($record, $clone);

        return $clone->refresh();
    }

    private function replicateOrderStatus(OrderStatus $record): OrderStatus
    {
        /** @var OrderStatus $clone */
        $clone = $this->replicateModel($record, [
            'code' => $this->uniqueModelValue($record, 'code'),
        ]);
        $this->cloneTranslations($record, $clone);

        return $clone->refresh();
    }

    private function replicateOrder(Order $record): Order
    {
        /** @var Order $clone */
        $clone = $this->replicateModel($record, [
            'number' => $this->uniqueModelValue($record, 'number'),
        ]);

        foreach ($record->items()->get() as $item) {
            $itemClone = $item->replicate();
            $itemClone->forceFill(['order_id' => $clone->getKey()]);
            $itemClone->saveQuietly();
        }

        return $clone->refresh();
    }

    private function replicateProductReview(ProductReview $record): ProductReview
    {
        /** @var ProductReview $clone */
        $clone = $this->replicateModel($record);
        $this->cloneReviewImages($record, $clone);

        foreach ($record->children()->get() as $child) {
            $childClone = $child->replicate();
            $childClone->forceFill(['parent_id' => $clone->getKey()]);
            $childClone->saveQuietly();
            $this->cloneReviewImages($child, $childClone);
        }

        return $clone->refresh();
    }

    private function cloneReviewImages(ProductReview $record, ProductReview $clone): void
    {
        foreach ($record->images()->get() as $image) {
            $imageClone = $image->replicate();
            $imageClone->forceFill(['review_id' => $clone->getKey()]);
            $imageClone->saveQuietly();
        }
    }

    private function replicateUser(User $record): User
    {
        /** @var User $clone */
        $clone = $this->replicateModel($record, [
            'email' => $this->uniqueEmail($record),
        ]);

        if (method_exists($record, 'roles')) {
            $clone->roles()->sync($record->roles()->pluck('roles.id')->all());
        }

        return $clone->refresh();
    }

    private function replicateGeneric(Model $record): Model
    {
        $clone = $this->replicateModel($record, $this->uniqueAttributes($record));

        if (method_exists($record, 'translations')) {
            $this->cloneTranslations($record, $clone);
        }

        $this->copyMedia($record, $clone);

        return $clone->refresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function replicateModel(Model $record, array $overrides = []): Model
    {
        $overrides = [
            ...$this->copyTitleOverrides($record),
            ...$overrides,
        ];
        $databaseColumns = Schema::getColumnListing($record->getTable());
        $computedAttributes = array_diff(array_keys($record->getAttributes()), $databaseColumns);
        $excludedAttributes = array_values(array_unique([
            ...$computedAttributes,
            ...array_keys($overrides),
        ]));

        $clone = $record->replicate($excludedAttributes);
        $clone->forceFill($overrides);
        $clone->saveQuietly();

        return $clone;
    }

    /**
     * @return array<int, Model>
     */
    private function cloneTranslations(Model $record, Model $clone): array
    {
        if (! method_exists($record, 'translations')) {
            return [];
        }

        $targetRelation = $clone->translations();
        $foreignKey = $targetRelation->getForeignKeyName();
        $translations = [];

        foreach ($record->translations()->get() as $translation) {
            $overrides = [];

            if (array_key_exists('slug', $translation->getAttributes())) {
                $overrides['slug'] = $this->uniqueTranslationSlug($translation);
            }

            foreach (['name', 'title'] as $column) {
                if (! array_key_exists($column, $translation->getAttributes()) || blank($translation->getRawOriginal($column))) {
                    continue;
                }

                $overrides[$column] = $this->copyTitle($translation, $column, (string) $translation->locale);

                break;
            }

            $translationClone = $translation->replicate(array_keys($overrides));
            $translationClone->forceFill([
                $foreignKey => $clone->getKey(),
                ...$overrides,
            ]);
            $translationClone->save();
            $translations[] = $translationClone;
        }

        return $translations;
    }

    /**
     * @return array<string, string>
     */
    private function copyTitleOverrides(Model $record): array
    {
        if ($record instanceof User || ! array_key_exists('name', $record->getAttributes())) {
            return [];
        }

        return [
            'name' => $this->copyTitle($record, 'name', Locales::default()),
        ];
    }

    private function copyTitle(Model $record, string $column, ?string $locale = null): string
    {
        $source = (string) $record->getRawOriginal($column);
        $base = trim((string) preg_replace('/\s*\((?:Копія|Копия|Copy)\s+\d+\)$/u', '', $source));
        $base = $base !== '' ? $base : $source;
        $number = 1;

        do {
            $label = (string) trans(
                'commero::admin.actions.clone.copy_suffix',
                ['number' => $number],
                $locale,
            );
            $candidate = trim($base.' ('.$label.')');
            $query = $record->newQuery()->where($column, $candidate);

            if ($locale !== null && array_key_exists('locale', $record->getAttributes())) {
                $query->where('locale', $locale);
            }

            $exists = $query->exists();
            $number++;
        } while ($exists);

        return $candidate;
    }

    /**
     * @param  array<int, Model>  $translations
     */
    private function syncHierarchicalPath(Model $clone, array $translations, bool $includeParentPath): void
    {
        $defaultTranslation = collect($translations)->first(
            fn (Model $translation): bool => $translation->locale === Locales::default(),
        );
        $slug = $defaultTranslation?->getAttribute('slug');

        if (blank($slug)) {
            return;
        }

        $basePath = trim((string) Str::slug((string) $slug), '/');

        if ($includeParentPath) {
            $parentPath = $clone->parent?->path;
            $basePath = $parentPath ? trim($parentPath.'/'.$basePath, '/') : $basePath;
        }

        $path = $this->uniquePath($clone, $basePath);
        $depth = $includeParentPath && $clone->parent
            ? (int) $clone->parent->depth + 1
            : ($includeParentPath ? 0 : (int) $clone->depth);

        $clone->forceFill([
            'path' => $path,
            'depth' => $depth,
        ])->saveQuietly();
    }

    private function uniquePath(Model $record, string $basePath): string
    {
        $basePath = trim($basePath, '/') ?: 'copy-'.$record->getKey();
        $candidate = $basePath;
        $counter = 2;

        while ($record->newQuery()->where('path', $candidate)->whereKeyNot($record->getKey())->exists()) {
            $candidate = $basePath.'-copy-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function uniqueTranslationSlug(Model $translation): string
    {
        $source = (string) ($translation->getRawOriginal('slug') ?: $translation->getRawOriginal('name'));
        $base = Str::slug($source) ?: 'copy-'.$translation->getKey();
        $locale = (string) $translation->getAttribute('locale');
        $candidate = $base.'-copy';
        $counter = 2;

        while ($this->translationSlugExists($translation, $locale, $candidate)) {
            $candidate = $base.'-copy-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function translationSlugExists(Model $translation, string $locale, string $slug): bool
    {
        if ($translation->newQuery()->where('locale', $locale)->where('slug', $slug)->exists()) {
            return true;
        }

        return Schema::hasTable('links')
            && DB::table('links')->where('locale', $locale)->where('slug', $slug)->exists();
    }

    /**
     * @param  Closure(Builder): Builder|null  $scope
     */
    private function uniqueModelValue(Model $record, string $column, ?Closure $scope = null): ?string
    {
        $value = $record->getRawOriginal($column);

        if (! is_string($value) || trim($value) === '') {
            return $value;
        }

        $maxLength = $record instanceof Currency && $column === 'code' ? 3 : 255;

        return $this->makeUniqueValue($record, $column, trim($value), $maxLength, $scope);
    }

    /**
     * @return array<string, mixed>
     */
    private function uniqueAttributes(Model $record): array
    {
        $attributes = [];

        if (array_key_exists('uuid', $record->getAttributes())) {
            $attributes['uuid'] = (string) Str::uuid();
        }

        foreach (['code', 'slug', 'identifier', 'number', 'sku'] as $column) {
            if (array_key_exists($column, $record->getAttributes())) {
                $attributes[$column] = $this->uniqueModelValue($record, $column);
            }
        }

        return $attributes;
    }

    private function uniqueEmail(User $record): ?string
    {
        $email = $record->getRawOriginal('email');

        if (! is_string($email) || trim($email) === '') {
            return $email;
        }

        [$local, $domain] = array_pad(explode('@', trim($email), 2), 2, 'example.com');
        $base = $local.'+copy';
        $candidate = $base.'@'.$domain;
        $counter = 2;

        while ($record->newQuery()->where('email', $candidate)->exists()) {
            $candidate = $base.'-'.$counter.'@'.$domain;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @param  Closure(Builder): Builder|null  $scope
     */
    private function makeUniqueValue(
        Model $record,
        string $column,
        string $value,
        int $maxLength = 255,
        ?Closure $scope = null,
    ): string {
        $base = trim($value);
        $counter = 1;

        do {
            if ($maxLength <= 8) {
                $suffix = (string) ($counter + 1);
                $candidate = substr($base, 0, max(1, $maxLength - strlen($suffix))).$suffix;
            } else {
                $suffix = $counter === 1 ? '-copy' : '-copy-'.$counter;
                $candidate = substr($base, 0, max(1, $maxLength - strlen($suffix))).$suffix;
            }

            $query = $record->newQuery()->where($column, $candidate);
            $exists = $scope ? $scope($query)->exists() : $query->exists();
            $counter++;
        } while ($exists);

        return $candidate;
    }

    private function syncBelongsToMany(Model $record, Model $clone, string $relation): void
    {
        $sourceRelation = $record->{$relation}();
        $targetRelation = $clone->{$relation}();

        if (! $sourceRelation instanceof BelongsToMany || ! $targetRelation instanceof BelongsToMany) {
            return;
        }

        $targetRelation->sync($sourceRelation->pluck($sourceRelation->getRelated()->getTable().'.'.$sourceRelation->getRelatedKeyName())->all());
    }

    private function cloneProductRelations(Product $record, Product $clone): void
    {
        foreach (['colorRelatedProducts' => 'color', 'boughtTogetherProducts' => 'bought_together'] as $relation => $type) {
            foreach ($record->{$relation}()->get() as $relatedProduct) {
                DB::table('product_relations')->insert([
                    'product_id' => $clone->getKey(),
                    'related_product_id' => $relatedProduct->getKey(),
                    'type' => $type,
                    'sort' => (int) ($relatedProduct->pivot?->sort ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function copyMedia(Model $record, Model $clone): void
    {
        if (! $record instanceof HasMedia || ! $clone instanceof HasMedia || ! method_exists($record, 'commeroMediaCollections')) {
            return;
        }

        foreach ($record->commeroMediaCollections() as $collection => $field) {
            $mediaItems = $record->getMedia($collection);

            foreach ($mediaItems as $media) {
                $media->copy($clone, $collection, $media->disk);
            }

            if ($mediaItems->isEmpty() && filled($clone->getAttribute($field))) {
                app(MediaService::class)->importLegacy($clone, $collection, $clone->getAttribute($field));
            }

            if ($mediaItems->isNotEmpty()) {
                app(MediaService::class)->importLegacy($clone, $collection);
            }
        }
    }
}
