<?php

return array (
  'navigation' => 
  array (
    'catalog' => 'Catalog',
    'marketing' => 'Marketing',
    'orders' => 'Orders',
    'content' => 'Content',
    'access' => 'Access',
    'payment_and_shipping' => 'Payment and Shipping',
    'system' => 'System',
  ),
  'common' => 
  array (
    'id' => 'ID',
    'code' => 'Code',
    'name' => 'Name',
    'title' => 'Title',
    'slug' => 'Slug',
    'sort' => 'Sort',
    'price' => 'Price',
    'availability' => 'Availability',
    'status' => 'Status',
    'type' => 'Type',
    'description' => 'Description',
    'excerpt' => 'Excerpt',
    'content' => 'Content',
    'full_description' => 'Full Description',
    'locale' => 'Locale',
    'translations' => 'Translations',
    'updated_at' => 'Updated',
    'created_at' => 'Created',
    'published_at' => 'Published at',
    'brand' => 'Brand',
    'group' => 'Group',
    'options' => 'Options',
    'label' => 'Label',
    'identifier' => 'Identifier',
    'value' => 'Value',
    'path' => 'Path',
    'depth' => 'Depth',
    'parent_category' => 'Parent category',
    'categories' => 'Categories',
    'sku' => 'SKU',
    'thumbnail' => 'Thumbnail',
    'icon' => 'Icon',
    'is_active' => 'Active',
    'url' => 'URL',
    'phone' => 'Phone',
    'email' => 'Email',
    'message' => 'Message',
    'save' => 'Save',
  ),
  'resources' => 
  array (
    'attribute_group' => 
    array (
      'singular' => 'attribute group',
      'plural' => 'attribute groups',
      'navigation' => 'Attribute Groups',
    ),
    'brand' => 
    array (
      'singular' => 'brand',
      'plural' => 'brands',
      'navigation' => 'Brands',
    ),
    'category' => 
    array (
      'singular' => 'category',
      'plural' => 'categories',
      'navigation' => 'Categories',
      'active_locale' => 'Translation locale',
      'active_locale_hint' => 'Choose which locale fields to edit.',
      'translation_fields' => 'Translation fields',
      'editing_translation' => 'Editing translation: :locale',
      'default_locale_required' => 'Fill in name and slug for the :locale locale.',
    ),
    'city_category' => 
    array (
      'singular' => 'city category',
      'plural' => 'city categories',
      'navigation' => 'City Categories',
      'active_locale' => 'Translation locale',
      'active_locale_hint' => 'Choose which locale fields to edit.',
      'translation_fields' => 'Translation fields',
      'editing_translation' => 'Editing translation: :locale',
      'default_locale_required' => 'Fill in name and slug for the :locale locale.',
    ),
    'product_attribute' => 
    array (
      'singular' => 'attribute',
      'plural' => 'attributes',
      'navigation' => 'Attributes',
      'active_locale' => 'Translation locale',
      'active_locale_hint' => 'Choose which locale fields to edit.',
      'translation_fields' => 'Translation fields',
      'editing_translation' => 'Editing translation: :locale',
      'default_locale_required' => 'Fill in name for the :locale locale.',
    ),
    'product' => 
    array (
      'singular' => 'product',
      'plural' => 'products',
      'navigation' => 'Products',
      'active_locale' => 'Translation locale',
      'active_locale_hint' => 'Choose which locale fields to edit.',
      'translation_fields' => 'Translation fields',
      'editing_translation' => 'Editing translation: :locale',
      'default_locale_required' => 'Fill in name and slug for the :locale locale.',
    ),
    'product_review' => 
    array (
      'singular' => 'review',
      'plural' => 'reviews',
      'navigation' => 'Reviews',
    ),
    'post' => 
    array (
      'singular' => 'post',
      'plural' => 'posts',
      'navigation' => 'Posts',
      'default_locale_required' => 'Fill in title and slug for the :locale locale.',
      'editor' => 
      array (
        'mode' => 
        array (
          'label' => 'Editor mode',
          'visual' => 'Visual',
          'html' => 'HTML',
        ),
        'html_source' => 'HTML source',
        'html_source_hint' => 'Edit the raw HTML of the article content.',
        'accent_quote' => 
        array (
          'label' => 'Accent quote',
          'modal_heading' => 'Insert accent quote',
          'accent_text' => 'Accent text',
          'body_text' => 'Body text',
          'preview_label' => 'Accent quote: :text',
        ),
        'video_embed' => 
        array (
          'label' => 'Video embed',
          'modal_heading' => 'Insert video embed',
          'url' => 'Video URL',
          'helper_text' => 'Paste a YouTube or Vimeo link.',
          'width' => 'Video width',
          'width_helper_text' => 'Optional. Set the maximum video width in pixels.',
          'validation' => 'Use a valid YouTube or Vimeo URL.',
          'preview_label' => 'Embedded video: :provider',
          'iframe_title' => ':provider video',
        ),
      ),
    ),
    'post_category' => 
    array (
      'singular' => 'post category',
      'plural' => 'post categories',
      'navigation' => 'Post Categories',
      'active_locale' => 'Translation locale',
      'active_locale_hint' => 'Choose which locale fields to edit.',
      'translation_fields' => 'Translation fields',
      'editing_translation' => 'Editing translation: :locale',
      'default_locale_required' => 'Fill in name and slug for the :locale locale.',
    ),
    'page' => 
    array (
      'singular' => 'page',
      'plural' => 'pages',
      'navigation' => 'Pages',
      'active_locale' => 'Translation locale',
      'active_locale_hint' => 'Choose which locale fields to edit.',
      'translation_fields' => 'Translation fields',
      'editing_translation' => 'Editing translation: :locale',
      'default_locale_required' => 'Fill in title and slug for the :locale locale.',
      'robots_default' => 'Default: index, follow',
    ),
    'order' => 
    array (
      'singular' => 'order',
      'plural' => 'orders',
      'navigation' => 'Orders',
    ),
    'order_status' => 
    array (
      'singular' => 'order status',
      'plural' => 'order statuses',
      'navigation' => 'Order Statuses',
      'default_locale_required' => 'Fill in name for the :locale locale.',
      'color' => 'Color',
      'badge_background_color' => 'Badge background color',
      'text_color' => 'Text color',
      'is_default_for_new_order' => 'Default status',
      'is_default_for_new_order_hint' => 'This status will be automatically assigned to new orders',
      'delete_confirm' => 'Are you sure you want to delete this order status?',
    ),
    'payment_method' => 
    array (
      'singular' => 'payment method',
      'plural' => 'payment methods',
      'navigation' => 'Payment Methods',
      'default_locale_required' => 'Fill in name for the :locale locale.',
    ),
    'shipping_method' => 
    array (
      'singular' => 'shipping method',
      'plural' => 'shipping methods',
      'navigation' => 'Shipping Methods',
      'default_locale_required' => 'Fill in name for the :locale locale.',
    ),
    'site_setting' => 
    array (
      'singular' => 'site settings',
      'plural' => 'site settings',
      'navigation' => 'Site Settings',
    ),
    'currency' => 
    array (
      'singular' => 'currency',
      'plural' => 'currencies',
      'navigation' => 'Currencies',
    ),
    'user' => 
    array (
      'singular' => 'user',
      'plural' => 'users',
      'navigation' => 'Users',
      'first_name' => 'First name',
      'last_name' => 'Last name',
      'phone' => 'Phone',
      'password' => 'Password',
      'roles' => 'Roles',
      'email_verified_at' => 'Email verified',
    ),
    'role' => 
    array (
      'singular' => 'role',
      'plural' => 'roles',
      'navigation' => 'Roles and permissions',
    ),
    'menu' => 
    array (
      'singular' => 'menu',
      'plural' => 'menus',
      'navigation' => 'Menus',
      'translation_fields' => 'Translation fields',
      'editing_translation' => 'Editing translation: :locale',
    ),
    'marketing_lead' => 
    array (
      'singular' => 'lead',
      'plural' => 'leads',
      'navigation' => 'Leads',
    ),
  ),
  'menu' => 
  array (
    'tabs' => 
    array (
      'main' => 'Main',
      'items' => 'Menu Items',
    ),
    'items' => 'Menu Items',
    'items_count' => 'Items count',
    'identifier_hint' => 'System key for quick menu access in templates, for example: footer-information',
    'add_item' => 'Add menu item',
    'add_translation' => 'Add translation',
    'open_in_new_tab' => 'Open in a new tab',
    'default_locale_required' => 'Menu items #:items must include a :locale translation with both label and URL.',
  ),
  'order' => 
  array (
    'order_section' => 'Order',
    'customer_section' => 'Customer details',
    'user_section' => 'Authenticated user',
    'user' => 'User',
    'user_profile' => 'User profile',
    'delivery_payment_section' => 'Shipping and payment',
    'other_recipient_section' => 'Another recipient',
    'has_other_recipient' => 'Recipient is another person',
    'recipient_first_name' => 'Recipient first name',
    'recipient_last_name' => 'Recipient last name',
    'recipient_phone' => 'Recipient phone',
    'recipient_email' => 'Recipient email',
    'number' => 'Order number',
    'source' => 'Source',
    'source_checkout' => 'Checkout',
    'source_quick_order' => 'Quick order',
    'customer_name' => 'Customer name',
    'customer_phone' => 'Phone',
    'customer_email' => 'Email',
    'comment' => 'Comment',
    'items' => 'Items',
    'product' => 'Product',
    'variant' => 'Variant',
    'variant_attributes' => 'Variant properties',
    'quantity' => 'Quantity',
    'thumbnail' => 'Thumbnail',
    'total_amount' => 'Total amount',
    'payment_method_code' => 'Payment method code',
    'payment_method_name' => 'Payment method name',
    'shipping_method_code' => 'Shipping method code',
    'shipping_method_name' => 'Shipping method name',
    'delivery_city_name' => 'Delivery city',
    'delivery_city_ref' => 'Delivery city ref',
    'delivery_warehouse_name' => 'Delivery branch',
    'delivery_warehouse_ref' => 'Delivery branch ref',
    'delivery_street' => 'Street',
    'delivery_house' => 'House',
    'delivery_apartment' => 'Apartment',
    'status' => 
    array (
      'new' => 'New',
      'processing' => 'Processing',
      'completed' => 'Completed',
      'cancelled' => 'Cancelled',
    ),
  ),
  'marketing_lead' => 
  array (
    'type' => 'Lead type',
    'name' => 'Name',
    'subject' => 'Subject',
    'source_url' => 'Page URL',
    'form_data' => 'Form data',
    'client_meta' => 'Client metadata',
    'internal_note' => 'Internal note',
    'processed_at' => 'Processed at',
    'types' => 
    array (
      'callback' => 'Callback request',
      'contact_form' => 'Contact form',
      'product_waitlist' => 'Back-in-stock request',
    ),
    'statuses' => 
    array (
      'new' => 'New',
      'processed' => 'Processed',
    ),
  ),
  'site_setting' => 
  array (
    'general_section' => 'General settings',
    'delivery_section' => 'Delivery settings',
    'site_name' => 'Site name',
    'logo_path' => 'Logo',
    'footer_logo_path' => 'Footer logo',
    'favicon_svg_path' => 'Favicon SVG',
    'favicon_svg_path_hint' => 'Primary favicon in SVG format.',
    'favicon_png_path' => 'Favicon PNG',
    'favicon_png_path_hint' => 'PNG fallback favicon for browsers without SVG support.',
    'nova_poshta_api_key' => 'Nova Poshta API key',
    'nova_poshta_api_key_hint' => 'Used to search cities and branches on checkout.',
    'contacts' => 'Contacts',
    'social_links' => 'Social links',
    'contact_identifier_hint' => 'Unique template key, for example: phone, address, email, working_hours',
    'social_identifier_hint' => 'Unique template key, for example: instagram, facebook, telegram',
    'identifier_unique' => 'Identifiers must be unique. Duplicates: :identifiers',
    'saved' => 'Settings saved',
  ),
  'category' => 
  array (
    'tabs' => 
    array (
      'main' => 'Main',
      'content' => 'Content',
      'seo' => 'SEO',
    ),
    'actions' => 
    array (
      'view_on_site' => 'View on site',
    ),
  ),
  'city_category' => 
  array (
    'display_category_ids' => 'Categories to display',
  ),
  'product' => 
  array (
    'brand_id' => 'Brand',
    'category_ids' => 'Categories',
    'tabs' => 
    array (
      'main' => 'Main',
      'gallery' => 'Gallery',
      'additional' => 'Additional',
      'characteristics' => 'Characteristics',
      'variants' => 'Variants',
      'faq' => 'FAQ',
      'relations' => 'Relations',
      'seo' => 'SEO',
    ),
    'gallery' => 
    array (
      'bulk_upload' => 'Bulk upload images',
      'bulk_upload_hint' => 'Select multiple files to add them to the product gallery at once.',
    ),
    'badges' => 
    array (
      'is_hit_sales' => 'Enable badge "Best seller"',
      'is_on_sale' => 'Enable badge "Sale"',
      'is_new' => 'Enable badge "New"',
    ),
    'faq' => 
    array (
      'section_title' => 'Product Questions and Answers',
      'label' => 'Questions and Answers',
      'question' => 'Question',
      'answer' => 'Answer',
    ),
    'relations' => 
    array (
      'section_title' => 'Related products',
      'color_related_product_ids' => 'Product in another color',
      'color_related_product_ids_hint' => 'Selected products will be shown on the product page in the "Color" block.',
      'bought_together_product_ids' => 'Bought with this product',
      'bought_together_product_ids_hint' => 'Selected products will be shown in the "Complete your look" block.',
    ),
    'characteristics' => 
    array (
      'section_title' => 'Product characteristics',
      'label' => 'Characteristics',
      'attribute' => 'Characteristic',
      'is_priority' => 'Priority',
    ),
    'seo' => 
    array (
      'translations' => 'SEO Translations',
      'meta_title' => 'Meta Title',
      'meta_description' => 'Meta Description',
      'robots' => 'Robots',
      'robots_options' => 
      array (
        'index_follow' => 'Index, Follow',
        'noindex_follow' => 'Noindex, Follow',
        'index_nofollow' => 'Index, Nofollow',
        'noindex_nofollow' => 'Noindex, Nofollow',
      ),
    ),
    'variants' => 
    array (
      'section_title' => 'Product Variants',
      'label' => 'Variants',
      'old_price' => 'Old Price',
      'attributes' => 'Attributes',
    ),
    'status' => 
    array (
      'draft' => 'Draft',
      'published' => 'Published',
    ),
    'type' => 
    array (
      'simple' => 'Simple',
      'variant' => 'Variant',
    ),
    'stock_status' => 
    array (
      'in_stock' => 'In stock',
      'out_of_stock' => 'Out of stock',
      'preorder' => 'Pre-order',
    ),
    'actions' => 
    array (
      'view_on_site' => 'Open product',
    ),
  ),
  'page' => 
  array (
    'actions' => 
    array (
      'view_on_site' => 'Open page',
    ),
  ),
  'post' => 
  array (
    'actions' => 
    array (
      'view_on_site' => 'Open post',
    ),
  ),
  'product_attribute' => 
  array (
    'group_id' => 'Attribute group',
    'value_type' => 'Value type',
    'is_filterable' => 'Use in filters',
    'is_required' => 'Required',
    'is_variant_axis' => 'Variant axis',
    'value_types' => 
    array (
      'string' => 'Text',
      'text' => 'Text',
      'integer' => 'Integer',
      'numeric' => 'Numeric',
      'boolean' => 'Boolean',
      'select' => 'Option list',
      'option' => 'Option list',
    ),
  ),
  'content' => 
  array (
    'tabs' => 
    array (
      'main' => 'Main',
      'content' => 'Content',
      'design' => 'Design',
      'seo' => 'SEO',
    ),
    'page_settings' => 'Page settings',
    'page_background_desktop' => 'Page background color desktop',
    'page_background_mobile' => 'Page background color mobile',
    'show_breadcrumbs' => 'Show breadcrumbs',
    'show_title' => 'Show page title',
    'add_block' => 'Add block',
    'status' => 
    array (
      'draft' => 'Draft',
      'published' => 'Published',
    ),
    'seo' => 
    array (
      'translations' => 'SEO Translations',
      'meta_title' => 'Meta Title',
      'meta_description' => 'Meta Description',
      'robots' => 'Robots',
      'robots_options' => 
      array (
        'index_follow' => 'Index, Follow',
        'noindex_follow' => 'Noindex, Follow',
        'index_nofollow' => 'Index, Nofollow',
        'noindex_nofollow' => 'Noindex, Nofollow',
      ),
    ),
  ),
  'product_review' => 
  array (
    'display_name' => 'Author name',
    'product_link' => 'Linked product',
    'author_type' => 'Author type',
    'author_types' => 
    array (
      'guest' => 'Guest',
      'user' => 'User',
      'admin' => 'Admin',
    ),
    'rating' => 'Rating',
    'photos' => 'Photos',
    'photo' => 'Photo',
    'photo_alt' => 'Photo alt text',
    'photos_count' => 'Photos count',
    'replies_count' => 'Replies count',
    'created_at' => 'Created at',
    'add_reply' => 'Add reply',
    'edit_reply' => 'Edit reply',
    'status' => 
    array (
      'pending' => 'Pending',
      'approved' => 'Approved',
      'rejected' => 'Rejected',
    ),
    'actions' => 
    array (
      'approve' => 'Approve',
      'reject' => 'Reject',
      'approve_selected' => 'Approve selected',
      'reject_selected' => 'Reject selected',
      'view_reviews' => 'Reviews',
    ),
  ),
  'locale_names' => 
  array (
    'uk' => 'Ukrainian',
    'ru' => 'Russian',
    'en' => 'English',
  ),
  'currency' => 
  array (
    'symbol' => 'Symbol',
    'is_base' => 'Base currency',
    'rate' => 'Rate to base',
  ),
);
